<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Energy/water rebalance: sources priced so each pays back in 50-85 days of full use
 * (against the new tariffs in App\Libraries\Utilities), snow guns use 10x the water, and
 * existing sources get the new output. Anyone who built a source at the old, higher price
 * is refunded the difference.
 */
class RebalanceUtilities extends Migration
{
    /** resource_key => [cost, build_days, output, upkeep] */
    private const NEW = [
        'grid' => [5000, 1, 1000, 150], 'generator' => [12000, 2, 2000, 350],
        'solar' => [30000, 4, 1500, 15], 'wind' => [45000, 6, 2500, 60],
        'well' => [4000, 3, 8000, 20], 'river_pump' => [6000, 3, 15000, 50],
        'reservoir' => [15000, 5, 25000, 40], 'recycling_plant' => [25000, 8, 40000, 100],
    ];
    /** resource_key => [cost, build_days, capacity, output, upkeep] before this migration */
    private const OLD = [
        'grid' => [10000, 1, 500, 500, 800], 'generator' => [25000, 2, 300, 300, 1200],
        'solar' => [75000, 4, 200, 200, 100], 'wind' => [120000, 6, 400, 400, 200],
        'well' => [30000, 3, 40000, 10000, 150], 'river_pump' => [40000, 3, 60000, 15000, 400],
        'reservoir' => [50000, 5, 100000, 20000, 300], 'recycling_plant' => [150000, 8, 80000, 25000, 500],
    ];
    private const TABLES = ['energy' => ['energy_management', 'output_kwh', 'capacity_kwh'], 'water' => ['water_management', 'output_liters', 'capacity_liters']];

    public function up()
    {
        $db = $this->db;
        $category = array_column($db->table('resource_types')->select('resource_key, category')->get()->getResultArray(), 'category', 'resource_key');
        $now = date('Y-m-d H:i:s');
        $db->transStart();

        foreach (self::NEW as $key => [$cost, $days, $output, $upkeep]) {
            $db->table('resource_types')->where('resource_key', $key)->update(['cost' => $cost, 'build_days' => $days, 'capacity' => $output, 'output' => $output, 'upkeep' => $upkeep]);
            if (!isset($category[$key])) continue;
            [$table, $outCol, $capCol] = self::TABLES[$category[$key]];
            $refund = self::OLD[$key][0] - $cost;
            foreach ($db->table($table)->where('source_type', $key)->get()->getResultArray() as $src) {
                $db->table($table)->where('id', $src['id'])->update([$outCol => $output, $capCol => $output, 'cost_per_day' => $upkeep]);
                if ($refund <= 0) continue;
                $db->table('player_finances')->where('user_id', $src['user_id'])->set('cash', 'cash + ' . $refund, false)->update();
                $db->table('financial_transactions')->insert([
                    'user_id' => $src['user_id'], 'game_day' => function_exists('getGameDay') ? getGameDay() : 1, 'category' => ucfirst($category[$key]),
                    'description' => 'Price change refund: ' . $src['name'], 'amount' => $refund, 'type' => 'income', 'created_at' => $now,
                ]);
                $db->table('notifications')->insert([
                    'user_id' => $src['user_id'], 'type' => $category[$key], 'title' => 'Refund for ' . $src['name'],
                    'message' => 'Energy and water sources got cheaper and more productive. You paid ' . number_format(self::OLD[$key][0]) . ' for ' . $src['name'] . ', which now costs ' . number_format($cost) . ': the difference (' . number_format($refund) . ') is back in your account.',
                    'icon' => 'fa-solid fa-coins', 'link' => '/' . $category[$key], 'is_read' => 0, 'created_at' => $now,
                ]);
            }
        }

        // Snow guns: water use x10 so water sources have demand to serve
        $db->query("UPDATE equipment_catalog SET water_liters = water_liters * 10 WHERE equipment_type = 'snowmaker'");
        $db->query("UPDATE equipment SET water_liters = water_liters * 10 WHERE equipment_type = 'snowmaker'");

        $db->transComplete();
    }

    public function down()
    {
        $db = $this->db;
        $category = array_column($db->table('resource_types')->select('resource_key, category')->get()->getResultArray(), 'category', 'resource_key');
        foreach (self::OLD as $key => [$cost, $days, $capacity, $output, $upkeep]) {
            $db->table('resource_types')->where('resource_key', $key)->update(['cost' => $cost, 'build_days' => $days, 'capacity' => $capacity, 'output' => $output, 'upkeep' => $upkeep]);
            if (!isset($category[$key])) continue;
            [$table, $outCol, $capCol] = self::TABLES[$category[$key]];
            $db->table($table)->where('source_type', $key)->update([$outCol => $output, $capCol => $capacity, 'cost_per_day' => $upkeep]);
        }
        $db->query("UPDATE equipment_catalog SET water_liters = water_liters DIV 10 WHERE equipment_type = 'snowmaker'");
        $db->query("UPDATE equipment SET water_liters = water_liters DIV 10 WHERE equipment_type = 'snowmaker'");
    }
}
