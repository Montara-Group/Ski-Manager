<?php

namespace App\Libraries;

/**
 * Own-pace seasons: one player finishes their season. Their result joins that season's final board
 * (season_leaderboard_archives, ranked by season profit among everyone who has finished so far),
 * then their resort starts the next season (SeasonReset::applyFor). Runs inside Clock::advance's
 * transaction.
 */
class SeasonEnd
{
    public static function finish(int $userId, array $season, array $next): void
    {
        $db = db_connect();
        $seasonNumber = (int) $season['season_number'];

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
                'reputation'    => (int) ($p['reputation'] ?? 0),
                'total_slopes'  => (int) $p['slopes'],
                'total_lifts'   => (int) $p['lifts'],
                'reward_badge'  => 'Season finisher',
                'achieved_at'   => date('Y-m-d H:i:s'),
            ]);
            // Ranks among own-pace finishers move as more players finish
            $db->query(
                'UPDATE season_leaderboard_archives a
                 JOIN (SELECT id, ROW_NUMBER() OVER (ORDER BY season_profit DESC, achieved_at ASC) rn
                       FROM season_leaderboard_archives WHERE season_number = ? AND season_profit IS NOT NULL) r ON r.id = a.id
                 SET a.rank_position = r.rn',
                [$seasonNumber]
            );
        }

        log_activity($userId, 'Season', ($season['name'] ?? "Season {$seasonNumber}") . ' complete: ' . currency((int) ($p['profit'] ?? 0)) . ' season profit.', 'fa-solid fa-flag-checkered');
        SeasonReset::applyFor($userId, (int) $next['season_number'], $next['name'] ?? ('Season ' . $next['season_number']));
    }
}
