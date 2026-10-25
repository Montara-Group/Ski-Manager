<?php

namespace App\Controllers;

class Leaderboard extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        // Economy v2: rank this season's profit (resorts reset each season), one board per difficulty, no demo accounts
        $v2 = featureEnabled('economy_v2');
        $difficulties = ['easy', 'standard', 'hard'];
        $difficulty = null;
        if ($v2) {
            $difficulty = $this->request->getGet('difficulty');
            if ($difficulty !== 'all' && !in_array($difficulty, $difficulties, true)) {
                $mine = auth()->loggedIn() ? $db->table('player_finances')->select('difficulty')->where('user_id', auth()->id())->get()->getRow('difficulty') : null;
                $difficulty = in_array($mine, $difficulties, true) ? $mine : 'standard';
            }
        }
        $filter = $v2 ? " AND (u.status IS NULL OR u.status != 'demo')" . ($difficulty !== 'all' ? ' AND pf.difficulty = ' . $db->escape($difficulty) : '') : '';
        $order = $v2 ? 'net_profit DESC' : 'score DESC';

        $players = $db->query("
            SELECT 
                u.id,
                u.username,
                u.created_at,
                pf.cash,
                pf.total_income,
                pf.total_expenses,
                pf.difficulty,
                (SELECT COUNT(*) FROM staff s WHERE s.user_id = u.id AND s.status != 'fired') as staff_count,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type IN ('slope','downhill','crosscountry','snowpark','luge')) as slope_count,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type = 'lift') as lift_count,
                (SELECT COUNT(*) FROM buildings b WHERE b.user_id = u.id) as building_count,
                (CAST(pf.total_income AS SIGNED) - CAST(pf.total_expenses AS SIGNED)) as net_profit,
                (pf.cash + pf.total_income) as score
            FROM users u
            LEFT JOIN player_finances pf ON pf.user_id = u.id
            WHERE u.id NOT IN (1, 3)
            AND pf.cash IS NOT NULL{$filter}
            ORDER BY {$order}
        ")->getResultArray();

        $currentUserId = auth()->loggedIn() ? auth()->id() : 0;

        return view('leaderboard/index', [
            'players' => $players,
            'currentUserId' => $currentUserId,
            'v2' => $v2,
            'difficulty' => $difficulty,
        ]);
    }
}
