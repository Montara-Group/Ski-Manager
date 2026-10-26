<?php

namespace App\Controllers;

use App\Libraries\Weather as W;

/**
 * /weather: today's conditions and what they actually do in the game, the next five days
 * (generated exactly as they will happen, see App\Libraries\Weather) and the snow base so far.
 */
class Weather extends BaseController
{
    public function index(): string
    {
        helper('weather');
        $now = getCurrentWeather();
        $baseTemp = (int) $now['base_temp'];
        $condition = (string) $now['condition_name'];
        $gameDay = getGameDay();
        $winterDays = getWinterDays();

        $history = db_connect()->table('weather')->select('game_day, snow_base, snowfall, temp, condition_name')
            ->where('game_day <=', $gameDay)->where('game_day >', $gameDay - 21)->orderBy('game_day')->get()->getResultArray();

        $warm = $baseTemp > 0 || in_array($condition, ['Rain', 'Freezing Rain'], true);
        $quality = match (true) {
            (int) $now['snowfall'] >= 10 => ['Powder', 'Fresh powder on every slope: skiers love it (+3% each).', 'success'],
            in_array($condition, ['Rain', 'Freezing Rain'], true) => ['Icy', 'Rain freezes on the snow: icy slopes put skiers off (−2% each).', 'error'],
            $condition === 'Sunny' && $baseTemp >= 0 => ['Bare patches', 'Sun and warmth melt the surface; slopes without snow guns go bare (−5% each).', 'warning'],
            default => ['Packed or groomed', 'Normal snow; slopes groomed overnight count as groomed (+2% each).', 'info'],
        };

        return view('weather/index', [
            'now' => $now,
            'phase' => W::phase(getSeasonDay(), $winterDays),
            'seasonDay' => getSeasonDay(),
            'seasonLength' => getSeasonLength(),
            'winterDays' => $winterDays,
            'hourly' => getHourlyForecast($baseTemp, (int) $now['base_wind'], $condition),
            'snowWindow' => snowmakingWindow($baseTemp),
            'currentHour' => (int) date('G'),
            'forecast' => is_array($now['forecast']) ? $now['forecast'] : [],
            'history' => $history,
            'demandFactor' => W::demandFactor($condition, (int) $now['base_wind']),
            'windHold' => (int) $now['base_wind'] >= W::WIND_HOLD,
            'warm' => $warm,
            'freshSnow' => (int) $now['snowfall'] >= 5,
            'thinBase' => (int) $now['snow_base'] < W::THIN_BASE,
            'quality' => $quality,
        ]);
    }
}
