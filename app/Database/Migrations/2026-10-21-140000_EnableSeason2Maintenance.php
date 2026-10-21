<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnableSeason2Maintenance extends Migration
{
    public function up()
    {
        // 1. Ensure 'seasons' table has maintenance columns
        if ($this->db->tableExists('seasons')) {
            $fields = $this->db->getFieldNames('seasons');
            $addFields = [];

            if (!in_array('maintenance', $fields, true)) {
                $addFields['maintenance'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ];
            }
            if (!in_array('maintenance_until', $fields, true)) {
                $addFields['maintenance_until'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                ];
            }
            if (!in_array('maintenance_message', $fields, true)) {
                $addFields['maintenance_message'] = [
                    'type' => 'TEXT',
                    'null' => true,
                ];
            }

            if (!empty($addFields)) {
                $this->forge->addColumn('seasons', $addFields);
            }

            // 2. Set maintenance mode until 2026-10-25 18:00:00 (4 days)
            $maintenanceUntil = '2026-10-25 18:00:00';
            $maintenanceMsg   = 'Ski Manager is temporarily paused for 4 days of scheduled maintenance and active development for the launch of Season 2.';

            $this->db->table('seasons')->update([
                'maintenance'         => 1,
                'maintenance_until'   => $maintenanceUntil,
                'maintenance_message' => $maintenanceMsg,
            ]);

            // 3. Shift Season 2 start_date to 2026-10-25 and ensure it is inactive during maintenance
            $this->db->table('seasons')
                ->where('season_number', 2)
                ->update([
                    'start_date' => '2026-10-25',
                    'active'     => 0,
                ]);

            // 4. Ensure Season 1 remains baseline active season while maintenance is underway
            $this->db->table('seasons')
                ->where('season_number', 1)
                ->update([
                    'active' => 1,
                ]);
        }

        // 5. Revert premature automatic Sector 2 unlock so it unlocks with official Season 2 launch
        if ($this->db->tableExists('resort_sectors')) {
            $this->db->table('resort_sectors')
                ->where('resort_map', 'ParkCity')
                ->groupStart()
                    ->where('name', 'Sector 2')
                    ->orWhere('name', 'Sector 2 (Advanced Peaks)')
                ->groupEnd()
                ->update([
                    'released' => 0,
                    'visible'  => 1,
                ]);
        }

        // 6. Clean up premature automatic welcome activity notifications if any were inserted
        if ($this->db->tableExists('activity_log')) {
            $this->db->table('activity_log')
                ->like('message', 'Welcome to Season 2')
                ->delete();
        }
    }

    public function down()
    {
        if ($this->db->tableExists('seasons')) {
            $this->db->table('seasons')->update([
                'maintenance' => 0,
            ]);
        }
    }
}
