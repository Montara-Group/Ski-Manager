<?php

namespace App\Controllers;

use App\Models\MapSegmentModel;

class ResortMap extends BaseController
{
    private const RESORT_MAPS = [
        'ParkCity'       => ['name' => 'Park City',       'location' => 'Utah, USA',       'image' => '/img/ParkCity.jpg',       'admin_image' => '/img/ParkCity-Big.jpg', 'width' => 600, 'height' => 340],
        'DeerValley'     => ['name' => 'Deer Valley',     'location' => 'Utah, USA',       'image' => '/img/DeerValley.jpg',     'width' => 600, 'height' => 340],
        'AspenSnowmass'  => ['name' => 'Aspen Snowmass',  'location' => 'Colorado, USA',   'image' => '/img/AspenSnowmass.jpg',  'width' => 600, 'height' => 340],
        'BigSkyCombo'    => ['name' => 'Big Sky',         'location' => 'Montana, USA',    'image' => '/img/BigSkyCombo.jpg',    'width' => 600, 'height' => 340],
        'Vail'           => ['name' => 'Vail',            'location' => 'Colorado, USA',   'image' => '/img/Vail.jpg',           'width' => 600, 'height' => 340],
        'PalisadesTahoe' => ['name' => 'Palisades Tahoe', 'location' => 'California, USA', 'image' => '/img/PalisadesTahoe.jpg', 'width' => 600, 'height' => 340],
        'Killington'     => ['name' => 'Killington',      'location' => 'Vermont, USA',    'image' => '/img/Killington.jpg',     'width' => 600, 'height' => 340],
    ];

    public static function getResortMapNames(): array { return array_map(fn($m) => $m["name"], self::RESORT_MAPS); }
    private function getSelectedMap(): string
    {
        $userId = auth()->id();
        if ($userId) {
            $fin = db_connect()->table('player_finances')
                ->where('user_id', $userId)
                ->get()->getRowArray();
            if ($fin && !empty($fin['resort_map']) && isset(self::RESORT_MAPS[$fin['resort_map']])) {
                return $fin['resort_map'];
            }
        }
        return 'ParkCity';
    }

    private function isAdmin(): bool
    {
        if (!function_exists('auth') || !auth()->loggedIn()) {
            return false;
        }
        $user = auth()->user();
        return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
    }

    public function changeMap()
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');
        $map = $this->request->getPost('map') ?? $this->request->getPost('resort_map');

        // Check either key or name
        $foundKey = null;
        if ($map && isset(self::RESORT_MAPS[$map])) {
            $foundKey = $map;
        } else {
            foreach (self::RESORT_MAPS as $k => $info) {
                if ($info['name'] === $map) {
                    $foundKey = $k;
                    break;
                }
            }
        }

        if (!$foundKey) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid map selected.']);
            }
            return redirect()->to('/map')->with('error', 'Invalid map selected.');
        }

        $db = db_connect();
        $db->table('player_finances')->where('user_id', $userId)->update([
            'resort_map' => $foundKey,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'map' => $foundKey, 'mapName' => self::RESORT_MAPS[$foundKey]['name']]);
        }

        return redirect()->to('/map')->with('success', 'Switched trail map to ' . self::RESORT_MAPS[$foundKey]['name'] . '.');
    }

    public function index()
    {
        $resortMap  = $this->getSelectedMap();
        $mapConfig  = self::RESORT_MAPS[$resortMap] ?? self::RESORT_MAPS['ParkCity'];
        $isAdmin    = $this->isAdmin();
        if ($isAdmin && !empty($mapConfig['admin_image'])) {
            $mapConfig['image'] = $mapConfig['admin_image'];
        }
        $userId     = auth()->id();
        $db         = db_connect();

        $sectorsQuery = $db->table('resort_sectors')
            ->where('resort_map', $resortMap)
            ->orderBy('sort_order', 'ASC');
        if (!$isAdmin) {
            $sectorsQuery->where('visible', 1);
        }
        $sectors = $sectorsQuery->get()->getResultArray();

        $segModel = new MapSegmentModel();
        $segQuery = $segModel->where('resort_map', $resortMap)->where('active', 1);
        if (!$isAdmin) {
            $visibleIds = array_column($sectors, 'id');
            if ($visibleIds) {
                $segQuery->groupStart()
                    ->whereIn('sector', $visibleIds)
                    ->orWhere('sector', '0')
                    ->orWhere('sector IS NULL')
                    ->groupEnd();
            }
        }
        $segments = $segQuery->findAll();

        $builtSegmentIds = [];
        $builtItems      = [];
        $userCash        = 0;
        if ($userId) {
            $built = $db->table('player_items')
                ->select('id, segment_id, item_type, subtype, name, length_meters, capacity, difficulty, sector, status, snow_quality')
                ->where('user_id', $userId)
                ->where('segment_id IS NOT NULL')
                ->get()->getResultArray();
            $builtSegmentIds = array_column($built, 'segment_id');
            foreach ($built as $b) {
                $builtItems[(int) $b['segment_id']] = $b + ['salvage' => self::salvageValue($b)];
            }
            $finRow   = $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray();
            $userCash = (int) ($finRow['cash'] ?? 0);
        }

        $releasedSectorIds = array_column(array_filter($sectors, fn($s) => $s['released']), 'id');
        $lifts = $slopes = 0;
        foreach ($segments as $s) {
            $s['type'] === 'lift' ? $lifts++ : $slopes++;
        }

        return view('resort_map/index', [
            'resortMap'         => $resortMap,
            'resortMaps'        => self::RESORT_MAPS,
            'mapConfig'         => $mapConfig,
            'segments'          => $segments,
            'sectors'           => $sectors,
            'builtSegmentIds'   => $builtSegmentIds,
            'builtItems'        => $builtItems,
            'userCash'          => $userCash,
            'isAdmin'           => $isAdmin,
            'liftCount'         => $lifts,
            'slopeCount'        => $slopes,
            'segmentCount'      => count($segments),
            'releasedSectorIds' => $releasedSectorIds,
        ]);
    }

    public function getSegments()
    {
        $resortMap = $this->getSelectedMap();
        $isAdmin   = $this->isAdmin();
        $db        = db_connect();
        $segModel  = new MapSegmentModel();
        $query     = $segModel->where('resort_map', $resortMap)->where('active', 1);

        if (!$isAdmin) {
            $vis = $db->table('resort_sectors')
                ->select('id')
                ->where('resort_map', $resortMap)
                ->where('visible', 1)
                ->get()->getResultArray();
            $ids = array_column($vis, 'id');
            if ($ids) {
                $query->groupStart()
                    ->whereIn('sector', $ids)
                    ->orWhere('sector', '0')
                    ->orWhere('sector IS NULL')
                    ->groupEnd();
            }
        }

        return $this->response->setJSON($query->findAll());
    }

    public function saveSegment()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $data  = $this->request->getJSON(true);
        $model = new MapSegmentModel();

        $rawPoints = $data['points'] ?? [];
        if (is_string($rawPoints)) {
            $rawPoints = json_decode($rawPoints, true) ?? [];
        }
        $cleanPoints = [];
        foreach ($rawPoints as $pt) {
            if (is_array($pt) && count($pt) >= 2) {
                $cleanPoints[] = [round((float) ($pt[0] ?? 0), 4), round((float) ($pt[1] ?? 0), 4)];
            } elseif (is_array($pt) && isset($pt['lat'], $pt['lng'])) {
                $cleanPoints[] = [round((float) $pt['lat'], 4), round((float) $pt['lng'], 4)];
            }
        }

        if (count($cleanPoints) < 2) {
            return $this->response->setStatusCode(400)->setJSON([
                'success'    => false,
                'error'      => 'A segment requires at least 2 points.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $type       = trim((string) ($data['type'] ?? 'lift')) ?: 'lift';
        $difficulty = $type === 'lift' ? '' : trim((string) ($data['difficulty'] ?? ''));
        $sectorId   = (int) ($data['sector'] ?? 0);
        $lengthM    = max(1, (int) ($data['length_meters'] ?? 0));
        $name       = trim((string) ($data['name'] ?? '')) ?: 'Unnamed';

        $row = [
            'type'          => $type,
            'name'          => $name,
            'points'        => json_encode($cleanPoints),
            'length_meters' => $lengthM,
            'sector'        => $sectorId,
            'difficulty'    => $difficulty,
            'resort_map'    => $this->getSelectedMap(),
            'active'        => 1,
        ];

        if (!empty($data['id'])) {
            $segId = (int) $data['id'];
            $model->update($segId, $row);

            // Keep built player_items metadata in sync with updated segment details
            db_connect()->table('player_items')
                ->where('segment_id', $segId)
                ->update([
                    'name'          => $name,
                    'length_meters' => $lengthM,
                    'difficulty'    => $difficulty ?: null,
                    'sector'        => $sectorId,
                ]);

            return $this->response->setJSON([
                'success'    => true,
                'id'         => $segId,
                'segment'    => array_merge(['id' => $segId], $row),
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $id = (int) $model->insert($row);
        return $this->response->setJSON([
            'success'    => true,
            'id'         => $id,
            'segment'    => array_merge(['id' => $id], $row),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function deleteSegment($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        (new MapSegmentModel())->delete($id);
        return $this->response->setJSON([
            'success'    => true,
            'id'         => (int) $id,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function buildItem()
    {
        $userId = auth()->id();
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Login required', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $data      = $this->request->getJSON(true);
        $segmentId = $data['segment_id'] ?? null;
        $liftType  = $data['lift_type']  ?? 'chair_fixed';
        if ($liftType === 'fixed') {
            $liftType = 'chair_fixed';
        }
        $seats     = (int) ($data['seats'] ?? 4);
        $slopeType = $data['slope_type'] ?? null;

        if (!$segmentId) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing segment', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $segment = (new MapSegmentModel())->find($segmentId);
        if (!$segment) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Segment not found', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $db = db_connect();

        $exists = $db->table('player_items')
            ->where('user_id', $userId)
            ->where('segment_id', $segmentId)
            ->countAllResults();
        if ($exists) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Already built', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $length = (float) ($segment['length_meters'] ?? 0);
        $cost   = self::calcCost($segment['type'], $liftType, $seats, $length);

        $fin  = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $cash = (int) ($fin['cash'] ?? 0);
        if ($cash < $cost) {
            return $this->response->setStatusCode(400)->setJSON([
                'error'      => 'Not enough cash ($' . number_format($cost) . ' required, $' . number_format($cash) . ' available)',
                'cost'       => $cost,
                'cash'       => $cash,
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $db->table('player_finances')->where('user_id', $userId)->update(['cash' => $cash - $cost]);

        $itemData = [
            'user_id'       => $userId,
            'segment_id'    => (int) $segmentId,
            'item_type'     => $segment['type'] === 'lift' ? 'lift' : 'slope',
            'subtype'       => $segment['type'] === 'lift' ? $liftType : ($slopeType ?? $segment['type']),
            'name'          => $segment['name'] ?? 'Unnamed',
            'length_meters' => (int) ($segment['length_meters'] ?? 0),
            'capacity'      => match($segment['type'] === 'lift' ? $liftType : '') { 'button' => 1000, 'chair_fixed' => 2400, 'chair_detach' => 3400, 'gondola' => 3500, 'cable_car' => 4000, default => 0 },
            'difficulty'    => $segment['difficulty'] ?: null,
            'sector'        => (int) ($segment['sector'] ?? 0),
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        $db->table('player_items')->insert($itemData);
        // The map needs the row id (to demolish it later) and its salvage value
        $itemData['id']      = (int) $db->insertID();
        $itemData['salvage'] = self::salvageValue($itemData);

        return $this->response->setJSON([
            'success'    => true,
            'cost'       => $cost,
            'new_cash'   => $cash - $cost,
            'item'       => $itemData,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * Demolish a built lift or slope for a 25% salvage refund (same rate as Buildings::sell).
     * Answers JSON for the map, or redirects back with a flash message for plain form posts.
     */
    public function demolishItem($id = null)
    {
        $userId = auth()->id();
        $isAjax = $this->request->isAJAX();
        $fail = function (int $code, string $msg) use ($isAjax) {
            return $isAjax
                ? $this->response->setStatusCode($code)->setJSON(['error' => $msg, 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()])
                : redirect()->back()->with('error', $msg);
        };
        if (!$userId) {
            return $fail(401, 'Login required');
        }

        $db   = db_connect();
        $item = $db->table('player_items')->where('id', (int) $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$item) {
            return $fail(404, 'That lift or slope was not found.');
        }

        $refund = self::salvageValue($item);
        $label  = $item['item_type'] === 'lift' ? 'lift' : 'slope';

        $db->transStart();
        $db->table('player_items')->where('id', $item['id'])->delete();
        // Detach things that pointed at it rather than deleting them
        $db->table('equipment')->where('user_id', $userId)->where('assigned_to', 'slope_' . $item['id'])->update(['assigned_to' => null]);
        $db->table('terrain_parks')->where('user_id', $userId)->where('slope_id', $item['id'])->update(['slope_id' => null]);
        $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $item['id'])->delete();
        if ($refund > 0) {
            $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash + {$refund}", false)->update();
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => getGameDay(),
                'category' => 'Construction', 'description' => 'Demolished ' . $item['name'] . ' (Salvage Refund)',
                'amount' => $refund, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            return $fail(500, 'Could not demolish ' . $item['name'] . '. Nothing was changed.');
        }

        log_activity($userId, 'Construction', 'Demolished ' . $label . ' ' . $item['name'] . ' for ' . currency($refund) . ' salvage', 'fa-solid fa-trash-can');

        $msg = $item['name'] . ' demolished. ' . currency($refund) . ' refunded (25% salvage).';
        if (!$isAjax) {
            return redirect()->back()->with('success', $msg);
        }
        $fin = $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray();
        return $this->response->setJSON([
            'success'    => true,
            'message'    => $msg,
            'refund'     => $refund,
            'segment_id' => $item['segment_id'] !== null ? (int) $item['segment_id'] : null,
            'new_cash'   => (int) ($fin['cash'] ?? 0),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * 25% of what the item cost to build. Seats aren't stored, so this prices the cheapest
     * carrier for its lift type: the refund can come out lower than 25%, never higher.
     */
    public static function salvageValue(array $item): int
    {
        $meters = (float) ($item['length_meters'] ?? 0);
        if (($item['item_type'] ?? '') !== 'lift') {
            return (int) round($meters * 50 * 0.25);
        }
        $minSeats = match ($item['subtype'] ?? '') {
            'button' => 1, 'chair_detach' => 4, 'gondola' => 6, 'cable_car' => 20, default => 2,
        };
        return (int) round(self::calcCost('lift', $item['subtype'] ?? 'chair_fixed', $minSeats, $meters) * 0.25);
    }

    private static function calcCost(string $type, ?string $liftType, int $seats, float $meters): int
    {
        if ($type === 'lift') {
            $base = match ($liftType) {
                'button'       => 80,
                'chair_fixed'  => 150,
                'chair_detach' => 250,
                'gondola'      => 400,
                'cable_car'    => 600,
                default        => 150,
            };
            $mult = match ($seats) {
                1 => 0.7, 2 => 1.0, 3 => 1.15, 4 => 1.3, 6 => 1.6, 8 => 2.0, 10 => 2.5, 20 => 4.0, 30 => 5.0, default => 1.0,
            };
            return (int) ($meters * $base * $mult);
        }
        return (int) ($meters * 50);
    }

    public function saveMidstation($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $data = $this->request->getJSON(true);
        $id   = $id ?: ($data['segment_id'] ?? null);
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        (new MapSegmentModel())->update($id, ['midstations' => json_encode($data['midstations'] ?? [])]);
        return $this->response->setJSON(['success' => true, 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
    }

    public function createSector()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $data = $this->request->getJSON(true);
        $db   = db_connect();
        $row  = [
            'resort_map'      => $this->getSelectedMap(),
            'name'            => trim((string) ($data['name'] ?? 'New Sector')) ?: 'New Sector',
            'description'     => $data['description'] ?? '',
            'color'           => $data['color'] ?? '#3b82f6',
            'visible'         => 1,
            'released'        => 0,
            'sort_order'      => 0,
            'boundary_points' => json_encode($data['boundary_points'] ?? []),
        ];
        $db->table('resort_sectors')->insert($row);
        $newId = (int) $db->insertID();
        return $this->response->setJSON([
            'success'    => true,
            'id'         => $newId,
            'sector'     => array_merge(['id' => $newId], $row),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function toggleSector($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $db     = db_connect();
        $sector = $db->table('resort_sectors')->where('id', $id)->get()->getRowArray();
        if (!$sector) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $newVis = $sector['visible'] ? 0 : 1;
        $db->table('resort_sectors')->where('id', $id)->update(['visible' => $newVis]);
        return $this->response->setJSON([
            'success'    => true,
            'visible'    => (bool) $newVis,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function deleteSector($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        db_connect()->table('resort_sectors')->where('id', $id)->delete();
        // Reset segments that belonged to this deleted sector back to 0
        db_connect()->table('map_segments')->where('sector', (int) $id)->update(['sector' => 0]);
        return $this->response->setJSON([
            'success'    => true,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function saveSectorBoundary($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $data = $this->request->getJSON(true);
        $id   = $id ?: ($data['id'] ?? null);
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $bp = $data['boundary_points'] ?? [];
        db_connect()->table('resort_sectors')->where('id', $id)->update([
            'boundary_points' => json_encode($bp),
        ]);
        return $this->response->setJSON([
            'success'         => true,
            'id'              => (int) $id,
            'boundary_points' => $bp,
            'csrf_token'      => csrf_token(),
            'csrf_hash'       => csrf_hash(),
        ]);
    }

    public function createSectorWithBoundary()
    {
        return $this->createSector();
    }

    public function autoAssignSectors()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $resortMap = $this->getSelectedMap();
        $db        = db_connect();
        $sectors   = $db->table('resort_sectors')->where('resort_map', $resortMap)->get()->getResultArray();
        $segModel  = new MapSegmentModel();
        $segments  = $segModel->where('resort_map', $resortMap)->where('active', 1)->findAll();
        $count     = 0;
        $updated   = [];

        foreach ($segments as $seg) {
            $pts = json_decode($seg['points'], true);
            if (empty($pts)) continue;
            // Check start point, midpoint, and end point
            $midIdx     = (int) floor(count($pts) / 2);
            $testPoints = [$pts[0], $pts[$midIdx], $pts[count($pts) - 1]];
            foreach ($sectors as $sec) {
                $bp = json_decode($sec['boundary_points'], true);
                if (empty($bp) || count($bp) < 3) continue;
                $matched = false;
                foreach ($testPoints as $tp) {
                    if ($this->pip($tp, $bp)) {
                        $matched = true;
                        break;
                    }
                }
                if ($matched) {
                    $secId = (int) $sec['id'];
                    $segModel->update($seg['id'], ['sector' => $secId]);
                    $updated[(int) $seg['id']] = $secId;
                    $count++;
                    break;
                }
            }
        }
        return $this->response->setJSON([
            'success'    => true,
            'assigned'   => $count,
            'updated'    => $updated,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    private function pip(array $pt, array $poly): bool
    {
        $x = $pt[1] ?? $pt['lng'] ?? 0;
        $y = $pt[0] ?? $pt['lat'] ?? 0;
        $in = false;
        $n  = count($poly);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $poly[$i][0] ?? 0; $xi = $poly[$i][1] ?? 0;
            $yj = $poly[$j][0] ?? 0; $xj = $poly[$j][1] ?? 0;
            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $in = !$in;
            }
        }
        return $in;
    }
}
