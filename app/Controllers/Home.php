<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        // Agent discovery (RFC 8288 Link headers, RFC 9727 section 3)
        $this->response->setHeader('Link', implode(', ', [
            '</.well-known/api-catalog>; rel="api-catalog"; type="application/linkset+json"',
            '</openapi.json>; rel="service-desc"; type="application/vnd.oai.openapi+json"',
            '</developers>; rel="service-doc"; type="text/html"',
            '</sitemap.xml>; rel="describedby"; type="text/xml"',
        ]));

        $page = $this->pageData();
        return view('home', ['page' => $page]);
    }

    /**
     * Everything the home page shows. All of it is live game data: no number on the page is written by hand.
     */
    protected function pageData(): array
    {
        $db = db_connect();
        $notDemo = "(u.status IS NULL OR u.status != 'demo')";

        $playerCount = (int) $db->query("SELECT COUNT(*) n FROM users u WHERE u.id != 1 AND {$notDemo}")->getRow()->n;
        $runsBuilt = (int) $db->table('player_items')->where('item_type', 'slope')->countAllResults();
        $buildersThisWeek = (int) $db->query(
            "SELECT COUNT(DISTINCT pi.user_id) n FROM player_items pi JOIN users u ON u.id = pi.user_id
             WHERE pi.created_at >= NOW() - INTERVAL 7 DAY AND u.id != 1 AND {$notDemo}"
        )->getRow()->n;

        $seasonNumber = (int) getSeasonNumber();
        $seasonDay = (int) getSeasonDay();
        $seasonLength = max(1, (int) getSeasonLength());
        $season = $db->table('seasons')->where('season_number', $seasonNumber)->get()->getRowArray();
        $resortMap = (string) ($season['resort_map'] ?? 'ParkCity');

        $w = getCurrentWeather();
        $weather = $w ? [
            'condition' => (string) ($w['condition_name'] ?? 'Clear'),
            'temp'      => temp((int) $w['temp']),
            'snowBase'  => snow((int) ($w['snow_base'] ?? 0)),
            'newSnow'   => (int) ($w['snowfall'] ?? 0) > 0 ? snow((int) $w['snowfall']) : 'None',
            'wind'      => speed((int) ($w['wind'] ?? 0)),
            'snowGuns'  => !empty($w['can_make_snow']),
        ] : null;

        return [
            'isLoggedIn' => (bool) (function_exists('auth') && auth()->loggedIn()),
            'players'    => number_format($playerCount),
            'runsBuilt'  => number_format($runsBuilt),
            'buildersThisWeek' => number_format($buildersThisWeek),
            'season'     => [
                'number' => $seasonNumber,
                'name'   => (string) ($season['name'] ?? "Season {$seasonNumber}"),
                'day'    => $seasonDay,
                'length' => $seasonLength,
            ],
            'weather'   => $weather,
            'sectors'   => $this->sectors($resortMap),
            'seasons'   => $this->seasons($seasonNumber),
            'standings' => $this->standings(),
            'recent'    => $this->recentBuilds(),
        ];
    }

    /** Runs by difficulty and lifts in each sector players can build in, from the map itself */
    protected function sectors(string $resortMap): array
    {
        $db = db_connect();
        $rows = $db->query(
            "SELECT s.id, s.name,
                    SUM(m.type = 'lift') lifts,
                    SUM(m.type = 'snowpark') parks,
                    SUM(m.type = 'downhill' AND m.difficulty = 'green') green,
                    SUM(m.type = 'downhill' AND m.difficulty = 'blue') blue,
                    SUM(m.type = 'downhill' AND m.difficulty = 'black') black,
                    SUM(m.type = 'downhill' AND m.difficulty = 'double_black') doubleBlack
             FROM resort_sectors s
             LEFT JOIN map_segments m ON m.sector = s.id AND m.active = 1
             WHERE s.resort_map = ? AND s.visible = 1 AND s.released = 1
             GROUP BY s.id, s.name ORDER BY s.sort_order, s.id",
            [$resortMap]
        )->getResultArray();

        return array_map(fn(array $r) => [
            'name'        => (string) $r['name'],
            'green'       => (int) $r['green'],
            'blue'        => (int) $r['blue'],
            'black'       => (int) $r['black'],
            'doubleBlack' => (int) $r['doubleBlack'],
            'lifts'       => (int) $r['lifts'],
            'parks'       => (int) $r['parks'],
        ], $rows);
    }

    /** Seasons that exist in the game, oldest first */
    protected function seasons(int $current): array
    {
        $rows = db_connect()->table('seasons')->orderBy('season_number', 'ASC')->get()->getResultArray();
        return array_map(function (array $s) use ($current) {
            $n = (int) $s['season_number'];
            return [
                'number' => $n,
                'name'   => (string) $s['name'],
                'start'  => date('F j, Y', strtotime((string) $s['start_date'])),
                'state'  => $n < $current ? 'done' : ($n === $current ? 'live' : 'next'),
            ];
        }, $rows);
    }

    /** This season's top resorts by profit, ranked the way /leaderboard ranks them (all difficulties) */
    protected function standings(): array
    {
        $rows = db_connect()->query(
            "SELECT u.username, pf.resort_name,
                    CAST(pf.total_income AS SIGNED) - CAST(pf.total_expenses AS SIGNED) AS profit,
                    COALESCE(it.slopes, 0) slopes, COALESCE(it.lifts, 0) lifts
             FROM users u
             JOIN player_finances pf ON pf.user_id = u.id
             LEFT JOIN (SELECT user_id, SUM(item_type = 'slope') slopes, SUM(item_type = 'lift') lifts FROM player_items GROUP BY user_id) it ON it.user_id = u.id
             WHERE u.id NOT IN (1, 3) AND u.active = 1 AND (u.status IS NULL OR u.status != 'demo')
               AND COALESCE(it.slopes, 0) + COALESCE(it.lifts, 0) > 0
             ORDER BY profit DESC, pf.cash DESC, u.id ASC
             LIMIT 5"
        )->getResultArray();

        $out = [];
        foreach ($rows as $i => $r) {
            $profit = (int) $r['profit'];
            $out[] = [
                'rank'     => $i + 1,
                'username' => (string) $r['username'],
                'resort'   => (string) ($r['resort_name'] ?? ''),
                'runs'     => (int) $r['slopes'],
                'lifts'    => (int) $r['lifts'],
                'profit'   => ($profit > 0 ? '+' : '') . currency($profit),
                'positive' => $profit >= 0,
            ];
        }
        return $out;
    }

    /**
     * What players built lately: at most two per player, so one busy builder doesn't fill the list.
     * Names the game gave by default ("Slope Path 97") are left out; the trail sign says what it is.
     */
    protected function recentBuilds(): array
    {
        $rows = db_connect()->table('player_items pi')
            ->select('pi.name, pi.item_type, pi.difficulty, pi.created_at, u.username')
            ->join('users u', 'u.id = pi.user_id')
            ->where('u.id !=', 1)
            ->whereIn('pi.item_type', ['slope', 'lift'])
            ->groupStart()->where('u.status IS NULL')->orWhere('u.status !=', 'demo')->groupEnd()
            ->orderBy('pi.id', 'DESC')
            ->limit(40)
            ->get()
            ->getResultArray();

        $perPlayer = [];
        $out = [];
        foreach ($rows as $r) {
            $who = (string) $r['username'];
            if (($perPlayer[$who] = ($perPlayer[$who] ?? 0) + 1) > 2) continue;
            $name = trim((string) $r['name']);
            $isDefault = $name === '' || preg_match('/^(Slope Path|Lift Line|Slope|Lift|Run)\s*#?\d+$/i', $name);
            $out[] = [
                'username'   => $who,
                'kind'       => $r['item_type'] === 'lift' ? 'lift' : 'run',
                'difficulty' => in_array($r['difficulty'], ['green', 'blue', 'black', 'double_black'], true) ? $r['difficulty'] : 'blue',
                'name'       => $isDefault ? null : $name,
                'ago'        => function_exists('timeAgo') ? timeAgo((string) $r['created_at']) : '',
            ];
            if (count($out) === 6) break;
        }
        return $out;
    }

    public function maintenance(): string
    {
        $info = function_exists('getMaintenanceInfo') ? getMaintenanceInfo() : [
            'enabled'           => true,
            'until'             => '2026-10-25 18:00:00 UTC',
            'message'           => 'Ski Manager is temporarily paused for 4 days of scheduled maintenance and active development for Season 2.',
            'remaining_seconds' => 345600,
        ];
        return view('maintenance', ['maintenance' => $info]);
    }
}
