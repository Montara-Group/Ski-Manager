<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Real estate market: properties take days to build, and can be leased or sold.
 * building_levels follow App\Libraries\RealEstate::PROJECTS so net-worth and rating code agree.
 */
class RealEstateMarket extends Migration
{
    private const LEVELS = [1 => [450, 60], 2 => [1600, 250], 3 => [3000, 450]];
    private const OLD_LEVELS = [1 => [800, 100], 2 => [3500, 500], 3 => [5000, 800]];

    public function up()
    {
        $fields = $this->db->getFieldNames('buildings');
        $add = [];
        if (!in_array('build_days_left', $fields, true)) $add['build_days_left'] = ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'status'];
        if (!in_array('tenure', $fields, true)) $add['tenure'] = ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false, 'default' => 'lease', 'after' => 'build_days_left'];
        if (!in_array('sale_price', $fields, true)) $add['sale_price'] = ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'tenure'];
        if ($add) $this->forge->addColumn('buildings', $add);

        foreach (self::LEVELS as $level => [$rent, $upkeep]) {
            $this->db->table('building_levels')->where('type_key', 'real_estate')->where('level', $level)->update(['revenue' => $rent, 'upkeep' => $upkeep]);
        }
        $this->db->table('building_levels')->where('type_key', 'real_estate')->where('level', 1)->update(['name' => 'Chalet']);
        $this->db->table('building_types')->where('type_key', 'real_estate')->update(['description' => 'Develop chalets, apartments and villas: lease them for rent or sell them on your resort\'s property market']);
    }

    public function down()
    {
        foreach (self::OLD_LEVELS as $level => [$rent, $upkeep]) {
            $this->db->table('building_levels')->where('type_key', 'real_estate')->where('level', $level)->update(['revenue' => $rent, 'upkeep' => $upkeep]);
        }
        $this->db->table('building_levels')->where('type_key', 'real_estate')->where('level', 1)->update(['name' => 'Small Chalet']);
        $this->forge->dropColumn('buildings', ['build_days_left', 'tenure', 'sale_price']);
    }
}
