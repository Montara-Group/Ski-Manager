<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Daily bonus: remember each player's longest streak (the streak itself no longer stops at 7) */
class DailyBonusBestStreak extends Migration
{
    public function up()
    {
        if (!in_array('best_streak', $this->db->getFieldNames('daily_bonus'), true)) {
            $this->forge->addColumn('daily_bonus', ['best_streak' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'streak']]);
        }
        $this->db->query('UPDATE daily_bonus SET best_streak = GREATEST(best_streak, streak)');
    }

    public function down()
    {
        $this->forge->dropColumn('daily_bonus', 'best_streak');
    }
}
