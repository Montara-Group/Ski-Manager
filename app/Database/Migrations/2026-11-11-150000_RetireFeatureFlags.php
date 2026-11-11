<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Feature flag cleanup.
 * - Five flags no code checks, so switching them did nothing.
 * - economy_v2 and demo_v2: on for everyone since their release; the code now always uses them.
 * down() puts the rows back as they were on production on 11 Nov 2026.
 */
class RetireFeatureFlags extends Migration
{
    private const FLAGS = [
        ['activity_feed', 'Activity Feed', 'Live scrolling feed of resort activity on dashboard', 2],
        ['beta_auto_grooming', '[BETA] Auto-Grooming', 'Set grooming schedules instead of manual daily grooming (coming soon)', 1],
        ['beta_mobile_nav', '[BETA] Mobile Bottom Nav', 'Fixed bottom navigation bar on mobile devices', 2],
        ['live_visitors', 'Live Visitor Counter', 'Animated real-time visitor count on dashboard', 2],
        ['weather_events', 'Weather Events', 'Enable weather-related events', 2],
        ['economy_v2', 'Economy v2', 'Ticket demand, lift capacity and running costs, building occupancy, season-profit leaderboard', 2],
        ['demo_v2', 'Demo v2', 'Demo account rules, first-play panel and the save flow. Use 0 or 2: admins are never demo accounts', 2],
    ];

    public function up()
    {
        $this->db->table('feature_flags')->whereIn('flag_key', array_column(self::FLAGS, 0))->delete();
    }

    public function down()
    {
        foreach (self::FLAGS as [$key, $name, $description, $level]) {
            if ($this->db->table('feature_flags')->where('flag_key', $key)->countAllResults()) continue;
            $this->db->table('feature_flags')->insert([
                'flag_key' => $key, 'name' => $name, 'description' => $description, 'enabled' => $level,
            ]);
        }
    }
}
