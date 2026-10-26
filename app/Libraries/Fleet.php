<?php

namespace App\Libraries;

/**
 * Owned equipment (groomers and snow guns): what a repair costs, what a sale returns, and doing
 * either with the money recorded in finances. Used by /equipment and /snowmaking alike.
 */
class Fleet
{
    public const REPAIR_SHARE = 0.3;  // repairing from 0% costs this share of the machine's price
    public const RESALE_SHARE = 0.5;  // a machine in perfect condition sells for this share of its price

    /** Shop prices by model */
    public static function prices(): array
    {
        return array_map('intval', array_column(db_connect()->table('equipment_catalog')->select('model_key, cost')->get()->getResultArray(), 'cost', 'model_key'));
    }

    public static function repairCost(array $eq, ?array $prices = null): int
    {
        $price = ($prices ?? self::prices())[$eq['model_key']] ?? 0;
        return (int) round($price * self::REPAIR_SHARE * (100 - (int) $eq['condition_pct']) / 100);
    }

    public static function resaleValue(array $eq, ?array $prices = null): int
    {
        $price = ($prices ?? self::prices())[$eq['model_key']] ?? 0;
        return (int) round($price * self::RESALE_SHARE * (int) $eq['condition_pct'] / 100);
    }

    /**
     * Repair to 100%. A broken machine comes back switched off; otherwise it keeps its on/off state.
     * @return int|null the cost, or null when the player can't afford it
     */
    public static function repair(int $userId, array $eq): ?int
    {
        $db = db_connect();
        $cost = self::repairCost($eq);
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $cost)
            ->set('cash', 'cash - ' . $cost, false)->set('total_expenses', 'total_expenses + ' . $cost, false)->update();
        if ($cost > 0 && $db->affectedRows() !== 1) {
            $db->transRollback();
            return null;
        }
        $db->table('equipment')->where('id', $eq['id'])->update([
            'condition_pct' => 100, 'status' => $eq['status'] === 'broken' ? 'off' : $eq['status'], 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($cost > 0) {
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Equipment', 'description' => 'Repaired ' . $eq['name'],
                'amount' => $cost, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        log_activity($userId, 'Equipment', 'Repaired ' . $eq['name'] . ' for ' . currency($cost), 'fa-solid fa-wrench');
        return $cost;
    }

    /** Sell, freeing any staff assigned to it. @return int what the player received */
    public static function sell(int $userId, array $eq): int
    {
        $db = db_connect();
        $refund = self::resaleValue($eq);
        $db->transStart();
        $db->table('equipment')->where('id', $eq['id'])->delete();
        $db->table('staff')->where('user_id', $userId)->where('role', $eq['equipment_type'])->where('assigned_to', $eq['name'])->update(['assigned_to' => null]);
        if ($refund > 0) {
            $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash + ' . $refund, false)->set('total_income', 'total_income + ' . $refund, false)->update();
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Equipment', 'description' => 'Sold ' . $eq['name'],
                'amount' => $refund, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        log_activity($userId, 'Equipment', 'Sold ' . $eq['name'] . ' for ' . currency($refund), 'fa-solid fa-coins');
        return $refund;
    }
}
