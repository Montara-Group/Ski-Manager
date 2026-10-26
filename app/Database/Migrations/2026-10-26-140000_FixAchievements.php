<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Achievement definitions that described something the game couldn't measure get wording that
 * matches App\Libraries\Achievements::metrics(), and completions that came from measuring bugs
 * (a 5-star rating that compared an array, a hard-coded safety score of 92, 100% patrol cover
 * with no slopes) are cleared where they haven't been claimed yet.
 */
class FixAchievements extends Migration
{
    private const DEFS = [
        'green_energy' => ['name' => 'Renewable Power', 'description' => 'Cover all your energy demand with solar and wind alone'],
        'full_coverage' => ['description' => 'Have patrol stations covering every open slope'],
        'zero_fines' => ['description' => 'Go 30 days without an inspection fine'],
        'survive_summer' => ['description' => 'Reach the summer part of a season'],
        'summer_profit' => ['description' => 'Make a profit on a summer day'],
        'day_30' => ['description' => 'Be a resort manager for 30 days'],
        'day_100' => ['description' => 'Be a resort manager for 100 days'],
        'day_365' => ['description' => 'Be a resort manager for a full year'],
    ];
    private const BUGGED = ['rating_5', 'safety_80', 'full_coverage'];

    public function up()
    {
        foreach (self::DEFS as $key => $fields) {
            $this->db->table('achievement_defs')->where('achievement_key', $key)->update($fields);
        }
        $this->db->table('achievements')->whereIn('achievement_key', self::BUGGED)->where('claimed', 0)->update(['completed' => 0, 'progress' => 0]);
    }

    public function down()
    {
        $this->db->table('achievement_defs')->where('achievement_key', 'green_energy')->update(['name' => 'Green Resort', 'description' => 'Power your resort with only solar and wind']);
    }
}
