<?php

namespace App\Libraries;

/**
 * Real estate (economy v2): develop properties on a limited number of plots, then lease them
 * for daily rent or sell them for cash now plus a small daily management fee. Both rent and
 * sale prices follow the resort's property market, which rises with its rating, open slopes
 * and slope condition. Used by the tick and the /real-estate page.
 */
class RealEstate
{
    /** Projects by building level: build cost, days to build, base rent and upkeep per day while leased */
    public const PROJECTS = [
        1 => ['key' => 'chalet', 'name' => 'Chalet', 'icon' => 'fa-solid fa-house', 'cost' => 30000, 'days' => 3, 'rent' => 450, 'upkeep' => 60],
        2 => ['key' => 'apartments', 'name' => 'Apartment block', 'icon' => 'fa-solid fa-building', 'cost' => 120000, 'days' => 5, 'rent' => 1600, 'upkeep' => 250],
        3 => ['key' => 'villa', 'name' => 'Luxury villa', 'icon' => 'fa-solid fa-landmark', 'cost' => 250000, 'days' => 8, 'rent' => 3000, 'upkeep' => 450],
    ];

    public const BASE_PLOTS = 2;
    public const SLOPES_PER_PLOT = 2;
    public const MAX_PLOTS = 12;
    public const SALE_PREMIUM = 1.15;       // sale price = cost × premium × market × condition
    public const MANAGEMENT_FEE = 0.001;    // of the sale price, per day, for sold units
    public const SUMMER_RENT = 0.6;         // rent outside the winter season

    /** Plots available for development: more as the resort opens more slopes */
    public static function plots(int $openSlopes): int
    {
        return min(self::MAX_PLOTS, self::BASE_PLOTS + intdiv(max(0, $openSlopes), self::SLOPES_PER_PLOT));
    }

    /**
     * The resort's property market multiplier (0.5–1.8) and what drives it.
     * @return array{value: float, parts: array<string, float>}
     */
    public static function market(int $stars, int $openSlopes, int $avgSlopeCondition): array
    {
        $parts = [
            'base' => 0.5,
            'rating' => 0.15 * max(0, min(5, $stars)),
            'slopes' => 0.02 * min(15, max(0, $openSlopes)),
            'condition' => ($avgSlopeCondition - 60) / 200,
        ];
        return ['value' => round(max(0.5, min(1.8, array_sum($parts))), 2), 'parts' => $parts];
    }

    /** Rent for a leased property today */
    public static function rent(int $level, float $market, int $conditionPct, bool $isWinter): int
    {
        $p = self::PROJECTS[$level] ?? self::PROJECTS[1];
        return (int) round($p['rent'] * $market * ($isWinter ? 1 : self::SUMMER_RENT) * (0.5 + 0.5 * $conditionPct / 100));
    }

    /** What a buyer pays for a finished property today */
    public static function salePrice(int $level, float $market, int $conditionPct): int
    {
        $p = self::PROJECTS[$level] ?? self::PROJECTS[1];
        return (int) round($p['cost'] * self::SALE_PREMIUM * $market * (0.7 + 0.3 * $conditionPct / 100));
    }

    public static function managementFee(int $salePrice): int
    {
        return (int) round($salePrice * self::MANAGEMENT_FEE);
    }

    /** 10% of the build cost at 0% condition, less as condition is higher */
    public static function repairCost(int $level, int $conditionPct): int
    {
        $p = self::PROJECTS[$level] ?? self::PROJECTS[1];
        return (int) round($p['cost'] * 0.1 * (1 - $conditionPct / 100));
    }
}
