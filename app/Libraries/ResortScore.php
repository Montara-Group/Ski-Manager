<?php

namespace App\Libraries;

/**
 * Own-pace leaderboard score: four parts worth up to 250 points each (1000 in all), so a resort
 * that is fast but badly run doesn't win. Every part is a rate or a state, never a running total,
 * so players on different days compare fairly.
 *
 * - profit: season profit per day played, on a log scale (100 €/day ≈ 25 pts, 100,000 €/day = 250)
 * - rating: the star rating's score (resortRating(), 0 to 200)
 * - reputation: 0 to 100
 * - visitors: average visitors per day played, on a log scale (5,000/day = 250)
 *
 * Stored on player_clocks after every day (store()), so the leaderboard reads one column.
 */
class ResortScore
{
    public const PART_MAX = 250;
    private const PROFIT_FULL = 100000;
    private const VISITORS_FULL = 5000;

    /** @return array{score: int, parts: array{profit: int, rating: int, reputation: int, visitors: int}, profitPerDay: int, visitorsPerDay: int} */
    public static function compute(int $userId, int $daysPlayed, int $seasonVisitors): array
    {
        $days = max(1, $daysPlayed);
        $f = db_connect()->table('player_finances')->select('total_income, total_expenses, reputation')->where('user_id', $userId)->get()->getRowArray() ?? [];
        $profitPerDay = (int) round(((int) ($f['total_income'] ?? 0) - (int) ($f['total_expenses'] ?? 0)) / $days);
        $visitorsPerDay = (int) round($seasonVisitors / $days);
        $rating = function_exists('resortRating') ? resortRating($userId, true) : ['score' => 0, 'max' => 200];

        $log = fn(int $value, int $full) => $value <= 0 ? 0 : (int) round(self::PART_MAX * min(1, log10(1 + $value / 100) / log10(1 + $full / 100)));
        $parts = [
            'profit' => $log($profitPerDay, self::PROFIT_FULL),
            'rating' => (int) round(self::PART_MAX * min(1, (float) ($rating['score'] ?? 0) / max(1, (int) ($rating['max'] ?? 200)))),
            'reputation' => (int) round(self::PART_MAX * min(1, max(0, (int) ($f['reputation'] ?? 0)) / 100)),
            'visitors' => $log($visitorsPerDay * 100, self::VISITORS_FULL * 100),
        ];
        return ['score' => array_sum($parts), 'parts' => $parts, 'profitPerDay' => $profitPerDay, 'visitorsPerDay' => $visitorsPerDay];
    }

    /** Add the day's visitors to the season total and store the new score */
    public static function store(int $userId, int $visitorsToday): array
    {
        $db = db_connect();
        $row = $db->table('player_clocks')->select('game_day, season_visitors')->where('user_id', $userId)->get()->getRowArray();
        if (!$row) return [];
        $seasonVisitors = (int) $row['season_visitors'] + max(0, $visitorsToday);
        $result = self::compute($userId, (int) $row['game_day'], $seasonVisitors);
        $db->table('player_clocks')->where('user_id', $userId)->update([
            'season_visitors' => $seasonVisitors,
            'score' => $result['score'],
            'score_parts' => json_encode($result['parts'] + ['profitPerDay' => $result['profitPerDay'], 'visitorsPerDay' => $result['visitorsPerDay']]),
        ]);
        return $result;
    }
}
