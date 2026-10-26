<?php

namespace App\Libraries;

/**
 * Snow guns (equipment rows with equipment_type 'snowmaker'). A gun that is switched on runs on any
 * day with at least one hour cold enough for its model; on warmer days it stands idle and costs
 * nothing. Each running gun covers `capacity` open slopes: its priority slope (assigned_to
 * 'slope_<id>') first, then the worst-condition slopes nobody else covers. A covered slope is
 * protected from a thin natural base (App\Libraries\Grooming::decay).
 */
class Snowmaking
{
    /** Warmest hour (°C) each model can still make snow in; other models use Weather::SNOWMAKING_MAX_TEMP */
    public const MAX_TEMP = ['demaclenko_titan5' => 0, 'hkd_impulse' => 0];
    /** Models that make snow at any temperature */
    public const ALL_WEATHER = ['ta_aw50'];

    public const CREW_WEAR_RELIEF = 1; // a gun with a crew member assigned wears this much less per day

    public static function maxTemp(array $gun): ?int
    {
        if (in_array($gun['model_key'] ?? '', self::ALL_WEATHER, true)) return null;
        return self::MAX_TEMP[$gun['model_key'] ?? ''] ?? Weather::SNOWMAKING_MAX_TEMP;
    }

    /** Hours the gun can make snow on a day with this base temperature */
    public static function hours(array $gun, int $baseTemp): int
    {
        $max = self::maxTemp($gun);
        if ($max === null) return 24;
        return count(array_filter(self::hourlyOffsets(), fn($o) => $baseTemp + $o <= $max));
    }

    /** Switched on, working and cold enough today */
    public static function runs(array $gun, int $baseTemp): bool
    {
        return ($gun['status'] ?? '') === 'active' && (int) ($gun['condition_pct'] ?? 0) > 0 && self::hours($gun, $baseTemp) > 0;
    }

    /** @return array guns from $guns that run today */
    public static function running(array $guns, int $baseTemp): array
    {
        return array_values(array_filter($guns, fn($g) => self::runs($g, $baseTemp)));
    }

    /**
     * Which gun covers each open slope today.
     * @return array<int, int> slope id => gun id
     */
    public static function coverage(array $openSlopes, array $guns, int $baseTemp): array
    {
        $running = self::running($guns, $baseTemp);
        $open = array_column($openSlopes, null, 'id');
        $covered = [];
        $left = [];
        foreach ($running as $g) {
            $left[(int) $g['id']] = max(1, (int) ($g['capacity'] ?? 1));
            $slopeId = self::prioritySlope($g);
            if ($slopeId && isset($open[$slopeId]) && !isset($covered[$slopeId])) {
                $covered[$slopeId] = (int) $g['id'];
                $left[(int) $g['id']]--;
            }
        }
        $rest = array_filter($open, fn($s) => !isset($covered[(int) $s['id']]));
        usort($rest, fn($a, $b) => (int) $a['condition_pct'] <=> (int) $b['condition_pct']);
        foreach ($rest as $s) {
            $gunId = array_key_first(array_filter($left, fn($n) => $n > 0));
            if ($gunId === null) break;
            $covered[(int) $s['id']] = $gunId;
            $left[$gunId]--;
        }
        return $covered;
    }

    public static function prioritySlope(array $gun): ?int
    {
        return preg_match('/^slope_(\d+)$/', (string) ($gun['assigned_to'] ?? ''), $m) ? (int) $m[1] : null;
    }

    /** Hour-by-hour offsets from the day's base temperature (same curve as weather_helper) */
    private static function hourlyOffsets(): array
    {
        return [-4, -4, -3, -3, -4, -4, -3, -2, -1, 0, 1, 2, 3, 4, 4, 3, 2, 1, 0, -1, -2, -2, -3, -3];
    }
}
