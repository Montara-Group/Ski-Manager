<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Own-pace seasons (App\Libraries\Clock). Every resort gets its own season number and game day in
 * player_clocks, starting from today's shared day. Weather rows gain a season number so players on
 * different seasons can read their own day. The `own_pace` flag starts off: until it is on for a
 * player, the shared calendar still decides their day and the nightly tick keeps their clock in step.
 */
class OwnPaceSeasons extends Migration
{
    public function up()
    {
        $season = $this->db->table('seasons')->where('active', 1)->orderBy('season_number', 'DESC')->get()->getRowArray();
        $seasonNumber = (int) ($season['season_number'] ?? 1);
        $startDate = (string) ($season['start_date'] ?? date('Y-m-d'));
        $duration = max(1, (int) ($season['duration_days'] ?? 135));
        $sharedDay = min($duration, max(1, (int) ((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1));

        if (!$this->db->tableExists('player_clocks')) {
            $this->forge->addField([
                'user_id'            => ['type' => 'INT', 'unsigned' => true],
                'season_number'      => ['type' => 'INT', 'unsigned' => true],
                'game_day'           => ['type' => 'INT', 'unsigned' => true],
                'autopilot'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'last_advanced_at'   => ['type' => 'DATETIME', 'null' => true],
                'last_advanced_date' => ['type' => 'DATE', 'null' => true],
                'manual_count_date'  => ['type' => 'DATE', 'null' => true],
                'manual_count'       => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
                'season_started_at'  => ['type' => 'DATETIME', 'null' => true],
                'created_at'         => ['type' => 'DATETIME', 'null' => true],
                'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('user_id', true);
            $this->forge->addKey(['autopilot', 'last_advanced_date']);
            $this->forge->createTable('player_clocks');

            // Everyone starts on today's shared day. The tick already ran today, so today counts as advanced
            $this->db->query(
                'INSERT INTO player_clocks (user_id, season_number, game_day, autopilot, last_advanced_date, season_started_at, created_at, updated_at)
                 SELECT user_id, ?, ?, 1, CURDATE(), ?, NOW(), NOW() FROM player_finances',
                [$seasonNumber, $sharedDay, $startDate . ' 00:00:00']
            );
        }

        if ($this->db->tableExists('weather') && !$this->db->fieldExists('season_number', 'weather')) {
            $this->db->query('ALTER TABLE weather ADD season_number INT UNSIGNED NOT NULL DEFAULT 1 AFTER id');
            $this->db->query('UPDATE weather SET season_number = ?', [$seasonNumber]);
            // Two generators racing could insert the same day twice; keep the first, the unique key needs one
            $this->db->query('DELETE w1 FROM weather w1 JOIN weather w2 ON w1.game_day = w2.game_day AND w1.id > w2.id');
            $this->db->query('ALTER TABLE weather DROP INDEX game_day, ADD UNIQUE KEY season_day (season_number, game_day)');
            // Earlier seasons were archived to weather_season_N tables: bring them back under their number
            for ($n = 1; $n < $seasonNumber; $n++) {
                $archive = 'weather_season_' . $n;
                if (!$this->db->tableExists($archive)) continue;
                $this->db->query("INSERT IGNORE INTO weather (season_number, game_day, temp, condition_name, wind, snowfall, visibility, humidity, snow_base, forecast, created_at)
                    SELECT {$n}, game_day, temp, condition_name, wind, snowfall, visibility, humidity, snow_base, forecast, created_at FROM `{$archive}`");
            }
        }

        if ($this->db->tableExists('season_leaderboard_archives') && !$this->db->fieldExists('season_profit', 'season_leaderboard_archives')) {
            $this->forge->addColumn('season_leaderboard_archives', [
                'season_profit' => ['type' => 'BIGINT', 'null' => true, 'after' => 'cash'],
            ]);
        }

        if (!$this->db->table('feature_flags')->where('flag_key', 'own_pace')->countAllResults()) {
            $this->db->table('feature_flags')->insert([
                'flag_key'    => 'own_pace',
                'name'        => 'Own-pace seasons',
                'description' => 'Each resort has its own season clock: a Next day button (daily cap) and a nightly autopilot',
                'enabled'     => 0,
            ]);
        }
    }

    public function down()
    {
        $this->db->table('feature_flags')->where('flag_key', 'own_pace')->delete();

        if ($this->db->tableExists('season_leaderboard_archives') && $this->db->fieldExists('season_profit', 'season_leaderboard_archives')) {
            $this->forge->dropColumn('season_leaderboard_archives', 'season_profit');
        }

        if ($this->db->tableExists('weather') && $this->db->fieldExists('season_number', 'weather')) {
            $season = $this->db->table('seasons')->where('active', 1)->orderBy('season_number', 'DESC')->get()->getRowArray();
            // Only the shared season's rows fit the old one-season table; the archives still hold the rest
            $this->db->query('DELETE FROM weather WHERE season_number <> ?', [(int) ($season['season_number'] ?? 1)]);
            $this->db->query('ALTER TABLE weather DROP INDEX season_day, ADD INDEX game_day (game_day)');
            $this->db->query('ALTER TABLE weather DROP COLUMN season_number');
        }

        $this->forge->dropTable('player_clocks', true);
    }
}
