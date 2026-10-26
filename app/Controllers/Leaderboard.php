<?php

namespace App\Controllers;

/**
 * /leaderboard: this season's profit, one board per difficulty (economy v2: resorts restart each
 * season). Only resorts that exist are ranked; ties share a rank. Past seasons come from
 * season_leaderboard_archives.
 */
class Leaderboard extends BaseController
{
    public const SHOWN = 50;

    public function index(): string
    {
        $db = db_connect();
        $v2 = featureEnabled('economy_v2');
        $difficulties = ['easy', 'standard', 'hard'];
        $userId = auth()->loggedIn() ? (int) auth()->id() : 0;

        $difficulty = 'all';
        if ($v2) {
            $difficulty = (string) $this->request->getGet('difficulty');
            if ($difficulty !== 'all' && !in_array($difficulty, $difficulties, true)) {
                $mine = $userId ? $db->table('player_finances')->select('difficulty')->where('user_id', $userId)->get()->getRow('difficulty') : null;
                $difficulty = in_array($mine, $difficulties, true) ? $mine : 'standard';
            }
        }

        $where = "u.id NOT IN (1, 3) AND u.active = 1 AND (u.status IS NULL OR u.status != 'demo')";
        $binds = [];
        if ($v2 && $difficulty !== 'all') { $where .= ' AND pf.difficulty = ?'; $binds[] = $difficulty; }
        $rows = $db->query("
            SELECT u.id, u.username, u.created_at, pf.cash, pf.difficulty, pf.daily_visitors, pf.resort_name,
                   CAST(pf.total_income AS SIGNED) - CAST(pf.total_expenses AS SIGNED) AS net_profit,
                   pf.cash + pf.total_income AS score,
                   COALESCE(it.slopes, 0) AS slope_count, COALESCE(it.lifts, 0) AS lift_count,
                   COALESCE(b.n, 0) AS building_count, COALESCE(st.n, 0) AS staff_count
            FROM users u
            JOIN player_finances pf ON pf.user_id = u.id
            LEFT JOIN (SELECT user_id, SUM(item_type = 'slope') slopes, SUM(item_type = 'lift') lifts FROM player_items GROUP BY user_id) it ON it.user_id = u.id
            LEFT JOIN (SELECT user_id, COUNT(*) n FROM buildings GROUP BY user_id) b ON b.user_id = u.id
            LEFT JOIN (SELECT user_id, COUNT(*) n FROM staff WHERE status != 'fired' GROUP BY user_id) st ON st.user_id = u.id
            WHERE {$where}
            ORDER BY " . ($v2 ? 'net_profit DESC, pf.cash DESC' : 'score DESC') . ', u.id ASC', $binds)->getResultArray();

        // Only resorts that exist this season are ranked; a fresh account with nothing built isn't
        $ranked = []; $unranked = 0; $rank = 0; $prev = null;
        foreach ($rows as $i => $r) {
            if ((int) $r['slope_count'] + (int) $r['lift_count'] + (int) $r['building_count'] === 0) { $unranked++; continue; }
            $value = $v2 ? (int) $r['net_profit'] : (int) $r['score'];
            if ($value !== $prev) { $rank = count($ranked) + 1; $prev = $value; }
            $r['rank'] = $rank;
            $ranked[] = $r;
        }
        $me = null;
        foreach ($ranked as $r) {
            if ((int) $r['id'] === $userId) { $me = $r; break; }
        }

        return view('leaderboard/index', [
            'players' => array_slice($ranked, 0, self::SHOWN),
            'rankedCount' => count($ranked),
            'unranked' => $unranked,
            'me' => $me,
            'meOutsideTop' => $me && $me['rank'] > self::SHOWN,
            'meInBoard' => $userId && array_filter($rows, fn($r) => (int) $r['id'] === $userId),
            'currentUserId' => $userId,
            'v2' => $v2,
            'difficulty' => $difficulty,
            'seasonName' => getSeasonName(),
            'seasonDay' => getSeasonDay(),
            'pastSeasons' => $this->pastSeasons(),
        ]);
    }

    /** Top 10 of each finished season, demo accounts left out */
    private function pastSeasons(): array
    {
        $db = db_connect();
        if (!$db->tableExists('season_leaderboard_archives')) return [];
        $rows = $db->table('season_leaderboard_archives a')
            ->select('a.season_number, a.season_name, a.rank_position, a.username, a.resort_name, a.cash, a.total_slopes, a.total_lifts')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->groupStart()->where('u.status IS NULL')->orWhere('u.status !=', 'demo')->groupEnd()
            ->where('a.rank_position <=', 10)
            ->orderBy('a.season_number', 'DESC')->orderBy('a.rank_position', 'ASC')
            ->get()->getResultArray();
        $seasons = [];
        foreach ($rows as $r) {
            $seasons[(int) $r['season_number']]['name'] = $r['season_name'];
            $seasons[(int) $r['season_number']]['rows'][] = $r;
        }
        return $seasons;
    }
}
