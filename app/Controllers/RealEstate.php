<?php

namespace App\Controllers;

use App\Libraries\RealEstate as Rules;

/**
 * /real-estate: develop properties on the resort's plots, lease them for rent or sell them on the
 * property market. Rules in App\Libraries\RealEstate; rent and fees are paid by the nightly tick.
 */
class RealEstate extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $s = $this->state($userId);
        $isWinter = isWinterDay();

        $props = [];
        foreach ($s['properties'] as $p) {
            $lvl = (int) $p['level'];
            $cond = (int) $p['condition_pct'];
            $p['project'] = Rules::PROJECTS[$lvl] ?? Rules::PROJECTS[1];
            $p['state'] = match (true) {
                $p['tenure'] === 'sold' => 'sold',
                $p['status'] === 'upgrading' => 'building',
                $p['status'] === 'broken' => 'broken',
                $p['status'] === 'open' => 'leased',
                default => 'vacant',
            };
            $p['rent'] = Rules::rent($lvl, $s['market']['value'], $cond, $isWinter);
            $p['sale_now'] = Rules::salePrice($lvl, $s['market']['value'], $cond);
            $p['fee'] = Rules::managementFee((int) $p['sale_price']);
            $p['repair'] = Rules::repairCost($lvl, $cond);
            $props[] = $p;
        }

        $projects = [];
        foreach (Rules::PROJECTS as $lvl => $pr) {
            $rent = Rules::rent($lvl, $s['market']['value'], 100, $isWinter);
            $net = $rent - $pr['upkeep'];
            $projects[$lvl] = $pr + [
                'rent_now' => $rent, 'net_now' => $net,
                'payback' => $net > 0 ? (int) ceil($pr['cost'] / $net) : null,
                'sale_now' => Rules::salePrice($lvl, $s['market']['value'], 100),
            ];
        }

        $sold = array_filter($props, fn($p) => $p['state'] === 'sold');
        $leased = array_filter($props, fn($p) => $p['state'] === 'leased');
        return view('real_estate/index', $s + [
            'props' => $props,
            'projects' => $projects,
            'isWinter' => $isWinter,
            'rentPerDay' => array_sum(array_column($leased, 'rent')),
            'upkeepPerDay' => array_sum(array_map(fn($p) => (int) $p['upkeep_per_day'], $leased)),
            'feesPerDay' => array_sum(array_column($sold, 'fee')),
            'salesTotal' => array_sum(array_map(fn($p) => (int) $p['sale_price'], $sold)),
        ]);
    }

    public function develop()
    {
        $userId = auth()->id();
        $level = (int) $this->request->getPost('level');
        $pr = Rules::PROJECTS[$level] ?? null;
        if (!$pr) return redirect()->to('/real-estate')->with('error', 'Unknown project.');
        $s = $this->state($userId);
        if ($s['plotsFree'] <= 0) {
            return redirect()->to('/real-estate')->with('error', 'All your plots are used. Open more slopes to unlock plots (one per ' . Rules::SLOPES_PER_PLOT . ').');
        }

        $db = db_connect();
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $pr['cost'])->set('cash', 'cash - ' . $pr['cost'], false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/real-estate')->with('error', 'Not enough cash: a ' . $pr['name'] . ' costs ' . currency($pr['cost']) . '.');
        }
        $number = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'real_estate')->where('level', $level)->countAllResults() + 1;
        $name = $pr['name'] . ' ' . $number;
        $db->table('buildings')->insert([
            'user_id' => $userId, 'building_type' => 'real_estate', 'name' => $name, 'level' => $level,
            'capacity' => 0, 'revenue_per_day' => $pr['rent'], 'upkeep_per_day' => $pr['upkeep'], 'condition_pct' => 100,
            'status' => 'upgrading', 'build_days_left' => $pr['days'], 'tenure' => 'lease', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Real estate',
            'description' => 'Started building ' . $name, 'amount' => $pr['cost'], 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Real estate', 'Started building ' . $name . ' for ' . currency($pr['cost']), 'fa-solid fa-city');
        return redirect()->to('/real-estate')->with('success', $name . ' is under construction: ready in ' . $pr['days'] . ' days.');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $s = $this->state($userId);
        $p = $this->property($userId, $id);
        if (!$p || $p['tenure'] === 'sold' || $p['status'] === 'upgrading') {
            return redirect()->to('/real-estate')->with('error', 'That property can\'t be sold right now.');
        }
        $price = Rules::salePrice((int) $p['level'], $s['market']['value'], (int) $p['condition_pct']);

        $db = db_connect();
        $db->transStart();
        $db->table('buildings')->where('id', $id)->where('user_id', $userId)->where('tenure', 'lease')->update([
            'tenure' => 'sold', 'sale_price' => $price, 'status' => 'closed', 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($db->affectedRows() !== 1) { $db->transRollback(); return redirect()->to('/real-estate'); }
        $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash + ' . $price, false)->set('total_income', 'total_income + ' . $price, false)->update();
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Real estate',
            'description' => 'Sold ' . $p['name'], 'amount' => $price, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Real estate', 'Sold ' . $p['name'] . ' for ' . currency($price), 'fa-solid fa-handshake');
        return redirect()->to('/real-estate')->with('success', 'Sold ' . $p['name'] . ' for ' . currency($price) . '. You\'ll earn ' . currency(Rules::managementFee($price)) . '/day managing it.');
    }

    /** Lease out a vacant property, or take a leased one off the market (no rent, no upkeep) */
    public function toggle(int $id)
    {
        $userId = auth()->id();
        $p = $this->property($userId, $id);
        if (!$p || $p['tenure'] === 'sold' || !in_array($p['status'], ['open', 'closed'], true)) {
            return redirect()->to('/real-estate')->with('error', $p && $p['status'] === 'broken' ? 'It needs repairs before it can be leased.' : 'That property can\'t be changed right now.');
        }
        $new = $p['status'] === 'open' ? 'closed' : 'open';
        db_connect()->table('buildings')->where('id', $id)->update(['status' => $new, 'updated_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/real-estate')->with('success', $p['name'] . ($new === 'open' ? ' is leased out again.' : ' is vacant: no rent, no upkeep.'));
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $p = $this->property($userId, $id);
        if (!$p || $p['tenure'] === 'sold' || $p['status'] === 'upgrading' || (int) $p['condition_pct'] >= 100) {
            return redirect()->to('/real-estate')->with('error', 'Nothing to repair.');
        }
        $cost = Rules::repairCost((int) $p['level'], (int) $p['condition_pct']);

        $db = db_connect();
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $cost)->set('cash', 'cash - ' . $cost, false)->set('total_expenses', 'total_expenses + ' . $cost, false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/real-estate')->with('error', 'Not enough cash: the repair costs ' . currency($cost) . '.');
        }
        $db->table('buildings')->where('id', $id)->update(['condition_pct' => 100, 'status' => $p['status'] === 'broken' ? 'open' : $p['status'], 'updated_at' => date('Y-m-d H:i:s')]);
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Real estate',
            'description' => 'Repaired ' . $p['name'], 'amount' => $cost, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();
        return redirect()->to('/real-estate')->with('success', $p['name'] . ' repaired for ' . currency($cost) . '.');
    }

    private function property(int $userId, int $id): ?array
    {
        return db_connect()->table('buildings')->where('id', $id)->where('user_id', $userId)->where('building_type', 'real_estate')->get()->getRowArray();
    }

    /** Plots, market and properties for a player */
    private function state(int $userId): array
    {
        $db = db_connect();
        $slopes = $db->table('player_items')->select('condition_pct')->where('user_id', $userId)->where('item_type', 'slope')->where('status', 'open')->get()->getResultArray();
        $avgCond = $slopes ? (int) round(array_sum(array_column($slopes, 'condition_pct')) / count($slopes)) : 0;
        $stars = (int) (resortRating($userId)['stars'] ?? 0);
        $properties = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'real_estate')->orderBy('id')->get()->getResultArray();
        $plots = Rules::plots(count($slopes));

        return [
            'stars' => $stars,
            'openSlopes' => count($slopes),
            'avgCond' => $avgCond,
            'market' => Rules::market($stars, count($slopes), $avgCond),
            'plots' => $plots,
            'plotsFree' => max(0, $plots - count($properties)),
            'properties' => $properties,
            'cash' => (int) $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRow('cash'),
        ];
    }
}
