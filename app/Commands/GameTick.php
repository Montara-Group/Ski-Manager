<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Clock;
use App\Libraries\DayTick;

class GameTick extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'game:tick';
    protected $description = 'Process daily game tick: revenue, expenses, weather, morale, conditions, VIP guests';

    public function run(array $params)
    {
        $checkInId = null;
        if (class_exists('\\Sentry\\ClientBuilder') && !empty(getenv('SENTRY_DSN'))) {
            try {
                $checkInId = \Sentry\captureCheckIn(
                    'gametick-cron',
                    \Sentry\CheckInStatus::inProgress()
                );
            } catch (\Throwable $t) {}
        }

        if (function_exists('isMaintenanceMode') && isMaintenanceMode()) {
            CLI::write('Maintenance mode is active: Season 2 development underway. Game tick paused to safeguard player finances.', 'yellow');
            return;
        }

        $db = db_connect();
        if (function_exists('checkAutoSeasonRollover')) {
            checkAutoSeasonRollover();
        }
        $gameDay = getGlobalGameDay();
        $seasonDay = (($gameDay - 1) % max(1, (int) getGlobalSeason()['duration_days'])) + 1;
        $isWinter = $seasonDay <= (int) getGlobalSeason()['winter_days'];

        CLI::write("Game Tick: Day {$gameDay} (Season day {$seasonDay}, " . ($isWinter ? 'Winter' : 'Summer') . ")", 'green');

        // Shared-calendar resorts: every account, as before. Own-pace resorts are left to autopilot below
        $totalUsers = $db->table('users')->countAllResults();
        $batchSize = 100;
        $offset = 0;

        while ($offset < $totalUsers) {
            $users = $db->table('users')->select('id')->orderBy('id', 'ASC')->limit($batchSize, $offset)->get()->getResultArray();
            if (empty($users)) {
                break;
            }

            foreach ($users as $user) {
                $userId = (int) $user['id'];
                if (Clock::enabledFor($userId)) {
                    continue;
                }
                CLI::write("Processing user {$userId}...", 'yellow');
                try {
                    // CI4 keeps a failed transaction status until reset, so one player's error
                    // used to roll back every player processed after them
                    $db->resetTransStatus();
                    $db->transBegin();

                    $report = Clock::as($userId, fn() => DayTick::run($userId));
                    Clock::syncShared($userId, (int) $report['visitors']);

                    if ($db->transStatus() === false) {
                        $db->transRollback();
                        CLI::write("  Transaction failed for user {$userId}, rolled back.", "red");
                        log_message("error", "GameTick user {$userId} transaction status failed and was rolled back.");
                    } else {
                        $db->transCommit();
                        clearResortRatingCache($userId);
                    }
                } catch (\Throwable $e) {
                    $db->transRollback();
                    $db->resetTransStatus();
                    CLI::write("  ERROR processing user {$userId}: " . $e->getMessage(), "red");
                    log_message("error", "GameTick user {$userId} failed: " . $e->getMessage());
                    if (class_exists('\\Sentry\\SentrySdk')) {
                        \Sentry\captureException($e);
                    }
                }
            }
            $offset += $batchSize;
        }

        // Own-pace resorts: autopilot adds one day for players seen recently who didn't press Next day
        // yesterday. Idle players stay on their day: no income, no costs
        foreach (Clock::autopilotDue() as $userId) {
            if (!Clock::enabledFor($userId)) continue;
            CLI::write("Autopilot user {$userId}...", 'yellow');
            $result = Clock::advance($userId, 'autopilot');
            CLI::write('  ' . $result['message'], $result['ok'] ? 'white' : 'red');
        }

        // Storage Maintenance: Prune old activity logs & financial records to keep storage bounded
        try {
            $db->table('activity_log')->where('created_at <', date('Y-m-d H:i:s', strtotime('-90 days')))->delete();
            $db->table('financial_transactions')->where('created_at <', date('Y-m-d H:i:s', strtotime('-180 days')))->delete();
            $db->table('ticket_sales')->where('created_at <', date('Y-m-d H:i:s', strtotime('-180 days')))->delete();
            // Notifications: read ones after 30 days, everything after 90
            $db->table('notifications')->where('is_read', 1)->where('created_at <', date('Y-m-d H:i:s', strtotime('-30 days')))->delete();
            $db->table('notifications')->where('created_at <', date('Y-m-d H:i:s', strtotime('-90 days')))->delete();
        } catch (\Throwable $e) {
            // Non-blocking maintenance cleanup
        }

        if ($checkInId !== null) {
            try {
                \Sentry\captureCheckIn(
                    'gametick-cron',
                    \Sentry\CheckInStatus::ok(),
                    null,
                    $checkInId
                );
            } catch (\Throwable $t) {}
        }

        CLI::write('Game tick complete!', 'green');
    }
}
