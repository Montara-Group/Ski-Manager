<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Schema for economy v2. Changes nothing for players until the `economy_v2` flag is switched on;
 * the flag starts at admins-only.
 */
class AddEconomyV2 extends Migration
{
    public function up()
    {
        if (!in_array('seats', $this->db->getFieldNames('player_items'), true)) {
            $this->forge->addColumn('player_items', [
                'seats' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'capacity'],
            ]);
        }
        // What the last tick paid and charged, so pages show real numbers instead of estimates
        if (!in_array('last_tick', $this->db->getFieldNames('player_finances'), true)) {
            $this->forge->addColumn('player_finances', [
                'last_tick' => ['type' => 'TEXT', 'null' => true],
            ]);
        }

        if (!$this->db->tableExists('season_legacy')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'season_number' => ['type' => 'INT', 'unsigned' => true],
                'user_id'       => ['type' => 'INT', 'unsigned' => true],
                'net_worth'     => ['type' => 'BIGINT'],
                'legacy_bonus'  => ['type' => 'INT', 'unsigned' => true],
                'starting_cash' => ['type' => 'BIGINT'],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['season_number', 'user_id']);
            $this->forge->createTable('season_legacy');
        }

        if (!$this->db->table('feature_flags')->where('flag_key', 'economy_v2')->countAllResults()) {
            $this->db->table('feature_flags')->insert([
                'flag_key'    => 'economy_v2',
                'name'        => 'Economy v2',
                'description' => 'Ticket demand, lift capacity and running costs, building occupancy, season-profit leaderboard',
                'enabled'     => 1,
            ]);
        }
    }

    public function down()
    {
        $this->db->table('feature_flags')->where('flag_key', 'economy_v2')->delete();
        $this->forge->dropTable('season_legacy', true);
        $this->forge->dropColumn('player_finances', 'last_tick');
        $this->forge->dropColumn('player_items', 'seats');
    }
}
