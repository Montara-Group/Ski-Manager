<?php

namespace App\Libraries;

/**
 * Own-pace seasons: the end of one player's season, in two steps. Both run inside Clock::advance's
 * transaction.
 *
 * record(): on the season's last day the resort's final score joins that season's final board
 * (season_leaderboard_archives), Génépis are paid by how the score compares with everyone else in
 * the season, and the player is told. Their resort then keeps playing in free play.
 *
 * rollover(): once the next season is released, the next Next day (or autopilot) starts the resort
 * over in it: the next season's trail map, a legacy bonus (SeasonReset::applyFor), day 1.
 */
class SeasonEnd
{
    /** [share of the season's resorts at or above this score, Génépis, badge]; the last row is everyone else */
    public const REWARDS = [
        [0.10, 50, 'Top 10% finisher'],
        [0.25, 25, 'Top 25% finisher'],
        [1.00, 10, 'Season finisher'],
    ];

    public static function record(int $userId, array $season): array
    {
        $db = db_connect();
        $seasonNumber = (int) $season['season_number'];
        $clock = $db->table('player_clocks')->where('user_id', $userId)->get()->getRowArray();
        $score = (int) ($clock['score'] ?? 0);

        // Compared with every resort in this season that has played long enough to be ranked
        $minDays = (int) config('OwnPace')->minRankedDays;
        $total = (int) $db->query('SELECT COUNT(*) n FROM player_clocks WHERE season_number = ? AND game_day >= ?', [$seasonNumber, $minDays])->getRow()->n;
        $better = (int) $db->query('SELECT COUNT(*) n FROM player_clocks WHERE season_number = ? AND game_day >= ? AND score > ?', [$seasonNumber, $minDays, $score])->getRow()->n;
        $share = $total > 0 ? ($better + 1) / $total : 1.0;
        [, $genepis, $badge] = self::REWARDS[count(self::REWARDS) - 1];
        foreach (self::REWARDS as [$limit, $g, $b]) {
            if ($share <= $limit) { $genepis = $g; $badge = $b; break; }
        }

        $p = $db->query(
            "SELECT pf.cash, pf.reputation, pf.resort_name, u.username,
                    CAST(pf.total_income AS SIGNED) - CAST(pf.total_expenses AS SIGNED) AS profit,
                    (SELECT COUNT(*) FROM player_items i WHERE i.user_id = pf.user_id AND i.item_type = 'slope') AS slopes,
                    (SELECT COUNT(*) FROM player_items i WHERE i.user_id = pf.user_id AND i.item_type = 'lift') AS lifts
             FROM player_finances pf JOIN users u ON u.id = pf.user_id WHERE pf.user_id = ?",
            [$userId]
        )->getRowArray();

        if ($p && $db->tableExists('season_leaderboard_archives')
            && !$db->table('season_leaderboard_archives')->where('season_number', $seasonNumber)->where('user_id', $userId)->countAllResults()) {
            $db->table('season_leaderboard_archives')->insert([
                'season_number' => $seasonNumber,
                'season_name'   => $season['name'] ?? "Season {$seasonNumber}",
                'user_id'       => $userId,
                'username'      => $p['username'] ?? "Manager #{$userId}",
                'resort_name'   => $p['resort_name'] ?? null,
                'rank_position' => 0,
                'cash'          => $p['cash'],
                'season_profit' => (int) $p['profit'],
                'season_score'  => $score,
                'reputation'    => (int) ($p['reputation'] ?? 0),
                'total_slopes'  => (int) $p['slopes'],
                'total_lifts'   => (int) $p['lifts'],
                'reward_badge'  => $badge,
                'achieved_at'   => date('Y-m-d H:i:s'),
            ]);
            // The final board ranks finishers by score; ranks move as more players finish
            $db->query(
                'UPDATE season_leaderboard_archives a
                 JOIN (SELECT id, ROW_NUMBER() OVER (ORDER BY season_score DESC, achieved_at ASC) rn
                       FROM season_leaderboard_archives WHERE season_number = ? AND season_score IS NOT NULL) r ON r.id = a.id
                 SET a.rank_position = r.rn',
                [$seasonNumber]
            );

            if ($db->tableExists('genepis')) {
                $db->query('UPDATE genepis SET balance = balance + ?, total_earned = total_earned + ? WHERE user_id = ?', [$genepis, $genepis, $userId]);
                $db->table('genepis_log')->insert([
                    'user_id' => $userId, 'amount' => $genepis, 'type' => 'earned',
                    'reason' => ($season['name'] ?? "Season {$seasonNumber}") . ' complete: ' . $badge, 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            notify($userId, 'season', ($season['name'] ?? "Season {$seasonNumber}") . ' complete',
                "Final resort score {$score}: {$badge}, +{$genepis} Génépis. You can keep playing in free play until the next season opens.",
                'fa-solid fa-flag-checkered', '/leaderboard');
            log_activity($userId, 'Season', ($season['name'] ?? "Season {$seasonNumber}") . " complete: resort score {$score}, {$badge}.", 'fa-solid fa-flag-checkered');
        }

        $db->table('player_clocks')->where('user_id', $userId)->update(['finished_at' => date('Y-m-d H:i:s')]);
        return ['score' => $score, 'badge' => $badge, 'genepis' => $genepis];
    }

    /** Start the resort over in $next. Returns the clock columns to write */
    public static function rollover(int $userId, array $season, array $next): array
    {
        $db = db_connect();
        // A resort that reached the last day was recorded then; one that somehow skipped it is recorded now
        if (!$db->table('season_leaderboard_archives')->where('season_number', (int) $season['season_number'])->where('user_id', $userId)->countAllResults()) {
            self::record($userId, $season);
        }
        SeasonReset::applyFor($userId, (int) $next['season_number'], $next['name'] ?? ('Season ' . $next['season_number']));
        if (!empty($next['resort_map'])) {
            $db->table('player_finances')->where('user_id', $userId)->update(['resort_map' => $next['resort_map']]);
        }
        return [
            'season_number' => (int) $next['season_number'], 'game_day' => 1, 'season_started_at' => date('Y-m-d H:i:s'),
            'season_visitors' => 0, 'score' => 0, 'score_parts' => null, 'finished_at' => null,
        ];
    }
}
