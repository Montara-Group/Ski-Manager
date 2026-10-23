<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\MapSegmentModel;

class Api extends BaseController
{
    /**
     * Unified live API endpoint for native mobile applications and clients.
     * Returns authentic mountain map segments, sectors, terrain geometry, finances, and weather.
     */
    public function resortData(): ResponseInterface
    {
        $db = db_connect();
        $userId = function_exists('auth') && auth()->loggedIn() ? (int) auth()->id() : 0;

        // 1. Season Info
        $season = function_exists('getCurrentSeason') ? getCurrentSeason() : [
            'id'            => 1,
            'season_number' => 1,
            'name'          => 'Season 1: Park City',
            'resort_map'    => 'ParkCity',
            'duration_days' => 135,
            'winter_days'   => 100,
        ];

        // 2. Map Configuration
        $resortMap = 'ParkCity';
        if ($userId > 0) {
            $fin = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            if ($fin && !empty($fin['resort_map'])) {
                $resortMap = $fin['resort_map'];
            }
        }

        // 3. Authentic Database Map Segments
        $segModel = new MapSegmentModel();
        $rawSegments = $segModel->where('resort_map', $resortMap)->where('active', 1)->findAll();
        $segments = [];
        foreach ($rawSegments as $seg) {
            $pts = is_string($seg['points']) ? json_decode($seg['points'], true) : $seg['points'];
            $segments[] = [
                'id'            => (int) $seg['id'],
                'resort_map'    => $seg['resort_map'] ?? $resortMap,
                'type'          => $seg['type'],
                'difficulty'    => $seg['difficulty'] ?? null,
                'name'          => $seg['name'],
                'points'        => is_array($pts) ? $pts : [],
                'length_meters' => (int) ($seg['length_meters'] ?? 0),
                'sector'        => (int) ($seg['sector'] ?? 0),
                'active'        => (bool) ($seg['active'] ?? true),
            ];
        }

        // 4. Authentic Sectors
        $rawSectors = $db->table('resort_sectors')
            ->where('resort_map', $resortMap)
            ->where('visible', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
        $sectors = [];
        foreach ($rawSectors as $sec) {
            $bPts = !empty($sec['boundary_points']) && is_string($sec['boundary_points'])
                ? json_decode($sec['boundary_points'], true)
                : ($sec['boundary_points'] ?? []);
            $sectors[] = [
                'id'              => (int) $sec['id'],
                'resort_map'      => $sec['resort_map'],
                'name'            => $sec['name'],
                'color'           => $sec['color'] ?? '#3b82f6',
                'boundary_points' => is_array($bPts) ? $bPts : [],
                'released'        => (bool) ($sec['released'] ?? false),
                'visible'         => (bool) ($sec['visible'] ?? true),
            ];
        }

        // 5. Player or Baseline Mountain Items
        $playerFinances = null;
        $playerLifts = [];
        $playerSlopes = [];
        $playerBuildings = [];

        if ($userId > 0) {
            $playerFinances = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            $playerItems = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
            foreach ($playerItems as $it) {
                if ($it['item_type'] === 'lift') {
                    $playerLifts[] = $it;
                } else {
                    $playerSlopes[] = $it;
                }
            }
            $playerBuildings = $db->table('buildings')->where('user_id', $userId)->get()->getResultArray();
        }

        // Fallback default starting values matching Demo.php template
        if (!$playerFinances) {
            $playerFinances = [
                'cash'              => 250000,
                'difficulty'        => 'standard',
                'resort_map'        => 'ParkCity',
                'daily_visitors'    => 480,
                'total_income'      => 0,
                'total_expenses'    => 0,
            ];
        }

        // 6. Current Weather
        $gameDay = function_exists('getGameDay') ? getGameDay() : 42;
        $weatherRow = $db->table('weather')->where('game_day', $gameDay)->get()->getRowArray();
        if (!$weatherRow) {
            $weatherRow = [
                'game_day'       => $gameDay,
                'temperature'    => -4,
                'snowfall'       => 15,
                'base_depth'     => 142,
                'wind_speed'     => 14,
                'condition'      => 'Powder',
                'is_winter'      => 1,
            ];
        }

        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->setJSON([
                'status'         => 'success',
                'resort_name'    => 'Park City Mountain Resort',
                'resort_map'     => $resortMap,
                'map_dimensions' => ['width' => 600, 'height' => 340],
                'map_image_url'  => 'https://ski-manager.net/img/' . $resortMap . '_med.jpg',
                'map_image_low'  => 'https://ski-manager.net/img/' . $resortMap . '_low.jpg',
                'season'         => $season,
                'game_day'       => $gameDay,
                'finances'       => $playerFinances,
                'segments'       => $segments,
                'sectors'        => $sectors,
                'player_lifts'   => $playerLifts,
                'player_slopes'  => $playerSlopes,
                'buildings'      => $playerBuildings,
                'weather'        => $weatherRow,
            ]);
    }
}
