<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Own-pace seasons, second step:
 * - day bank (App\Libraries\Clock): Next day spends saved days, which refill over real time
 * - resort score (App\Libraries\ResortScore), stored after every day so the leaderboard is one query
 * - finished_at: when the resort reached its season's last day (final board, then free play)
 * - resort_sectors.release_season: the season a sector opens in, for players on their own season
 * - season_leaderboard_archives.season_score: the final resort score
 */
class OwnPaceBankScoreRelease extends Migration
{
    public function up()
    {
        $clockCols = [
            'bank_days'       => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0, 'after' => 'manual_count'],
            'bank_updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'bank_days'],
            'season_visitors' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0, 'after' => 'bank_updated_at'],
            'score'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'season_visitors'],
            'score_parts'     => ['type' => 'JSON', 'null' => true, 'after' => 'score'],
            'finished_at'     => ['type' => 'DATETIME', 'null' => true, 'after' => 'score_parts'],
        ];
        foreach ($clockCols as $name => $def) {
            if (!$this->db->fieldExists($name, 'player_clocks')) {
                $this->forge->addColumn('player_clocks', [$name => $def]);
            }
        }
        // Everyone starts with a full bank
        $this->db->query('UPDATE player_clocks SET bank_days = ?, bank_updated_at = NOW()', [(int) config('OwnPace')->bankMax]);
        if (!$this->db->query("SHOW INDEX FROM player_clocks WHERE Key_name = 'season_score'")->getResultArray()) {
            $this->db->query('ALTER TABLE player_clocks ADD INDEX season_score (season_number, score)');
        }

        if (!$this->db->fieldExists('release_season', 'resort_sectors')) {
            $this->forge->addColumn('resort_sectors', [
                'release_season' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'released'],
            ]);
        }

        if ($this->db->tableExists('season_leaderboard_archives') && !$this->db->fieldExists('season_score', 'season_leaderboard_archives')) {
            $this->forge->addColumn('season_leaderboard_archives', [
                'season_score' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'season_profit'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('season_leaderboard_archives') && $this->db->fieldExists('season_score', 'season_leaderboard_archives')) {
            $this->forge->dropColumn('season_leaderboard_archives', 'season_score');
        }
        if ($this->db->fieldExists('release_season', 'resort_sectors')) {
            $this->forge->dropColumn('resort_sectors', 'release_season');
        }
        if ($this->db->query("SHOW INDEX FROM player_clocks WHERE Key_name = 'season_score'")->getResultArray()) {
            $this->db->query('ALTER TABLE player_clocks DROP INDEX season_score');
        }
        foreach (['finished_at', 'score_parts', 'score', 'season_visitors', 'bank_updated_at', 'bank_days'] as $name) {
            if ($this->db->fieldExists($name, 'player_clocks')) {
                $this->forge->dropColumn('player_clocks', $name);
            }
        }
    }
}
