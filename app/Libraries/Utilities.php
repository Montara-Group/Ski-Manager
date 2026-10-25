<?php

namespace App\Libraries;

/**
 * Energy and water rules, shared by the nightly tick and the /energy and /water pages.
 * Sources (resource_types) produce a fixed amount per day while active; any demand above
 * that is bought from the municipal grid / water network at the tariff.
 */
class Utilities
{
    public const GRID_TARIFF = 0.15;   // EUR per kWh imported
    public const WATER_TARIFF = 0.002; // EUR per litre imported

    /** Daily energy draw per item */
    public const DRAW_NIGHT_LIGHT = 80;
    public const DRAW_LIFT = 30;
    public const DRAW_BUILDING = 20;

    /** Source types for 'energy' or 'water', keyed by resource_key */
    public static function config(string $category): array
    {
        static $cache = [];
        if (!isset($cache[$category])) {
            $cache[$category] = [];
            foreach (db_connect()->table('resource_types')->where('category', $category)->orderBy('sort_order')->get()->getResultArray() as $r) {
                $cache[$category][$r['resource_key']] = [
                    'label' => $r['label'], 'icon' => $r['icon'], 'cost' => (int) $r['cost'], 'build_days' => (int) $r['build_days'],
                    'capacity' => (int) $r['capacity'], 'output' => (int) $r['output'], 'upkeep' => (int) $r['upkeep'], 'decay' => (float) $r['decay_rate'],
                ];
            }
        }
        return $cache[$category];
    }

    /**
     * Energy draw (kWh/day) by consumer. Takes counts of what is running: active snowmakers'
     * energy_kwh, active night-skiing lights, open lifts and open buildings.
     */
    public static function energyDemand(array $activeSnowmakers, int $lights, int $openLifts, int $openBuildings): array
    {
        $d = [
            'snowmaking' => (int) array_sum(array_column($activeSnowmakers, 'energy_kwh')),
            'night_skiing' => $lights * self::DRAW_NIGHT_LIGHT,
            'lifts' => $openLifts * self::DRAW_LIFT,
            'buildings' => $openBuildings * self::DRAW_BUILDING,
        ];
        return $d + ['total' => array_sum($d)];
    }

    public static function waterDemand(array $activeSnowmakers): int
    {
        return (int) array_sum(array_column($activeSnowmakers, 'water_liters'));
    }

    public static function importCost(string $category, int $deficit): int
    {
        return (int) round(max(0, $deficit) * ($category === 'energy' ? self::GRID_TARIFF : self::WATER_TARIFF));
    }

    /**
     * What an active source is worth per day against importing: the import it avoids (only
     * as much of its output as is actually needed) minus its upkeep. Negative = costs more than importing.
     */
    public static function dailyValue(string $category, array $cfg, int $usefulOutput, float $costMult = 1.0): int
    {
        return self::importCost($category, $usefulOutput) - (int) round($cfg['upkeep'] * $costMult);
    }
}
