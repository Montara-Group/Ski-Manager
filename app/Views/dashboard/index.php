<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$__hour = (int) date('G');
$__greeting = $__hour < 12 ? 'Good morning' : ($__hour < 17 ? 'Good afternoon' : 'Good evening');
$seasonLength   = max(1, (int) getSeasonLength());
$seasonProgress = min($gameDay, $seasonLength);
$seasonNum      = (int) ceil($gameDay / $seasonLength);
$seasonPct      = (int) round($seasonProgress / $seasonLength * 100);
$resortOpen     = (bool) ($finance['resort_open'] ?? 1);
$cashBalance    = (int) ($finance['cash'] ?? 0);
$repScore       = (int) ($finance['reputation'] ?? 0);
$genepisBal     = (int) ($genepis['balance'] ?? 0);

$wIcons = [
    'Sunny'         => 'fa-sun text-warning',
    'Partly Cloudy' => 'fa-cloud-sun text-info',
    'Cloudy'        => 'fa-cloud text-base-content/50',
    'Light Snow'    => 'fa-snowflake text-info',
    'Heavy Snow'    => 'fa-snowflake text-primary',
    'Blizzard'      => 'fa-wind text-error',
    'Freezing Rain' => 'fa-cloud-rain text-error',
];

$totalParkingCap = 0;
$totalOccupied   = 0;
foreach ($parkingFacilities as $pf) {
    if ($pf['status'] === 'open' || $pf['status'] === 'full') {
        $totalParkingCap += (int) $pf['capacity'];
        $totalOccupied   += (int) $pf['occupied'];
    }
}
$parkingPct = $totalParkingCap > 0 ? (int) round($totalOccupied / $totalParkingCap * 100) : 0;

$openParks         = array_filter($terrainParks, fn($tp) => $tp['status'] === 'open');
$buildingParks     = array_filter($terrainParks, fn($tp) => $tp['status'] === 'under_construction');
$totalParkVisitors = array_sum(array_column($terrainParks, 'daily_visitors'));

$roles = [];
foreach ($staffAll as $s) {
    $roles[$s['role']] = ($roles[$s['role']] ?? 0) + 1;
}
$avgMorale = count($staffAll) > 0 ? (int) round(array_sum(array_column($staffAll, 'morale')) / count($staffAll)) : 0;
$activeIns = array_filter($insurance, fn($i) => ($i['active'] ?? 0) == 1);
$forecast  = $weather ? (is_array($weather['forecast'] ?? null) ? $weather['forecast'] : (json_decode($weather['forecast'] ?? '[]', true) ?? [])) : [];

$cf = $cashflow ?? [
    'ticket_price'    => 0,
    'ticket_income'   => 0,
    'building_income' => 0,
    'total_income'    => 0,
    'salaries'        => 0,
    'equipment_cost'  => 0,
    'marketing_cost'  => 0,
    'loan_payments'   => 0,
    'total_expenses'  => 0,
    'daily_profit'    => $dailyProfit ?? 0,
];
?>

<style>
.dash-shell {
    max-width: 1320px;
    margin: 0 auto;
    padding: 1.25rem 1rem 2.5rem;
}
@media (min-width: 1024px) {
    .dash-shell {
        padding: 1.75rem 2rem 3rem;
    }
}

/* Executive Hero Header */
.dash-hero {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.85) 0%, rgba(15, 23, 42, 0.95) 100%);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
}
.dash-hero-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}
.dash-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #e2e8f0;
}
.dash-pill-open {
    background: rgba(34, 197, 94, 0.15);
    border-color: rgba(34, 197, 94, 0.35);
    color: #4ade80;
}
.dash-pill-closed {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.35);
    color: #f87171;
}
.dash-pill-mountain {
    background: rgba(56, 189, 248, 0.14);
    border-color: rgba(56, 189, 248, 0.35);
    color: #38bdf8;
}

/* Top 4 Executive KPI Cards */
.dash-kpi-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.875rem;
    margin-bottom: 1.25rem;
}
@media (min-width: 1024px) {
    .dash-kpi-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
.dash-kpi-card {
    background: rgba(15, 23, 42, 0.78);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 14px;
    padding: 1rem 1.15rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.65rem;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
}
.dash-kpi-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}
.dash-kpi-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    margin-bottom: 4px;
}
.dash-kpi-value {
    font-size: 1.45rem;
    font-weight: 800;
    line-height: 1.15;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
.dash-kpi-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.dash-kpi-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 11px;
    color: #94a3b8;
    padding-top: 8px;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

/* Alert Strip */
.dash-alert-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

/* Edit Mode Banner */
.edit-mode-banner {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px;
    margin-bottom: 14px;
    border-radius: 12px;
    background: rgba(37, 99, 235, 0.18);
    border: 1px solid rgba(59, 130, 246, 0.45);
    color: #bfdbfe;
    font-size: 12px;
}
.edit-mode-active .edit-mode-banner {
    display: flex;
}

/* Widget Grid */
.widget-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
@media (max-width: 1024px) {
    .widget-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 640px) {
    .widget-grid {
        grid-template-columns: 1fr;
    }
}
.widget-small { grid-column: span 1; }
.widget-medium { grid-column: span 2; }
.widget-large { grid-column: span 4; }
@media (max-width: 1024px) {
    .widget-small { grid-column: span 1; }
    .widget-medium { grid-column: span 2; }
    .widget-large { grid-column: span 2; }
}
@media (max-width: 640px) {
    .widget-small, .widget-medium, .widget-large { grid-column: span 1; }
}

.widget-item {
    background: rgba(15, 23, 42, 0.78);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 15px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}
.widget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 12px;
}
.widget-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #e2e8f0;
}
.widget-title i {
    font-size: 13px;
}
.widget-link {
    font-size: 11px;
    font-weight: 700;
    color: #38bdf8;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.widget-content {
    padding: 1rem 1.15rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.widget-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    text-align: center;
    padding: 0.5rem 0;
}

/* Inner Sub-Cards & Tiles */
.dash-subcard {
    background: rgba(30, 41, 59, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 11px;
    padding: 10px 12px;
}
.dash-action-tile {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 11px;
    background: rgba(30, 41, 59, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    color: #f1f5f9;
    text-decoration: none;
}
.dash-action-tile:hover {
    background: rgba(37, 99, 235, 0.2);
    border-color: rgba(56, 189, 248, 0.45);
}
.dash-action-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}

/* Drag Handle for Layout Edit Mode */
.drag-handle {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 700;
    cursor: grab;
    user-select: none;
    background: rgba(30, 41, 59, 0.9);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.drag-handle:active { cursor: grabbing; }
.edit-mode .drag-handle { display: flex !important; }
.edit-mode .widget-item { outline: 2px dashed rgba(56, 189, 248, 0.35); }
.widget-item.dragging { opacity: 0.35; }
.widget-item.drag-over { outline-color: #38bdf8 !important; outline-width: 3px; }
</style>

<div class="dash-shell" id="dashRoot">

    <!-- Executive Resort Command Header -->
    <div class="dash-hero">
        <div>
            <div class="dash-hero-badges">
                <span class="dash-pill dash-pill-mountain">
                    <i class="fa-solid fa-mountain-sun"></i> <?= esc($resortMapName ?? 'Park City') ?>
                </span>
                <span class="dash-pill <?= $resortOpen ? 'dash-pill-open' : 'dash-pill-closed' ?>">
                    <span style="width:7px;height:7px;border-radius:50%;background:currentColor"></span>
                    <?= $resortOpen ? 'Resort Open' : 'Resort Closed' ?>
                </span>
                <span class="dash-pill">
                    <i class="fa-regular fa-calendar"></i> Season <?= $seasonNum ?> · Day <?= $gameDay ?>
                </span>
                <span class="dash-pill">
                    <i class="fa-solid fa-star text-warning"></i> <?= number_format($repScore) ?> Rep
                </span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight"><?= $__greeting ?>, <?= esc(auth()->user()->username) ?></h1>
            <p class="text-xs sm:text-sm text-base-content/60 mt-1">
                <?= $liftCount ?> open lift<?= $liftCount === 1 ? '' : 's' ?> &amp; <?= $slopeCount ?> open slope<?= $slopeCount === 1 ? '' : 's' ?>
                · Trail condition avg <b class="<?= ($avgSlopeCondition ?? 100) >= 70 ? 'text-success' : 'text-warning' ?>"><?= $avgSlopeCondition ?? 100 ?>%</b>
                · <?= date('l, F j') ?>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="/map" class="btn btn-primary btn-sm gap-1.5 font-bold shadow-md">
                <i class="fa-solid fa-map-location-dot"></i> Trail Map
            </a>
            <a href="/resort" class="btn btn-outline btn-sm gap-1.5">
                <i class="fa-solid fa-sliders"></i> Resort Ops
            </a>
            <?php if ($bonusAvailable): ?>
            <a href="/daily-bonus" class="btn btn-warning btn-sm gap-1.5 font-bold shadow-md">
                <i class="fa-solid fa-gift"></i> Claim Bonus
            </a>
            <?php endif; ?>
            <button id="editModeBtn" type="button" onclick="toggleEditMode()" class="btn btn-ghost btn-sm border border-white/10 gap-1.5">
                <i class="fa-solid fa-table-columns"></i> Customize
            </button>
        </div>
    </div>

    <!-- Layout Edit Mode Banner -->
    <div class="edit-mode-banner">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-wand-magic-sparkles text-info"></i>
            <span><b>Customize Dashboard:</b> Drag any widget header to reorder, click <b>S / M / L</b> to change width, or click the eye icon to hide/restore widgets.</span>
        </div>
        <button type="button" onclick="toggleEditMode()" class="btn btn-xs btn-primary font-bold">Done</button>
    </div>

    <!-- Priority Operational Alerts -->
    <?php if (!empty($alerts) || $unclaimedAchievements > 0) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 mb-4">
        <?php foreach ($alerts as $alert) : ?>
        <a href="<?= $alert['link'] ?>" class="alert alert-<?= $alert['type'] ?> py-2.5 px-3.5 text-xs font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <i class="<?= $alert['icon'] ?> text-sm"></i>
                <span><?= esc($alert['msg']) ?></span>
            </div>
            <span class="underline opacity-90 whitespace-nowrap ml-2">Resolve <i class="fa-solid fa-arrow-right ml-0.5"></i></span>
        </a>
        <?php endforeach ?>
        <?php if ($unclaimedAchievements > 0): ?>
        <a href="/achievements" class="alert alert-warning py-2.5 px-3.5 text-xs font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-trophy text-sm"></i>
                <span>You have <b><?= $unclaimedAchievements ?> unclaimed achievement reward<?= $unclaimedAchievements > 1 ? 's' : '' ?></b> waiting!</span>
            </div>
            <span class="underline whitespace-nowrap ml-2">Claim Rewards <i class="fa-solid fa-arrow-right ml-0.5"></i></span>
        </a>
        <?php endif; ?>
    </div>
    <?php endif ?>

    <!-- Top 4 Executive KPI Cards -->
    <div class="dash-kpi-grid">
        <!-- 1. Treasury & Balance -->
        <div class="dash-kpi-card">
            <div class="dash-kpi-top">
                <div>
                    <div class="dash-kpi-label">Treasury Balance</div>
                    <div class="dash-kpi-value <?= $cashBalance >= 0 ? 'text-success' : 'text-error' ?>"><?= currency($cashBalance) ?></div>
                </div>
                <div class="dash-kpi-icon" style="background:rgba(34,197,94,0.14);border:1px solid rgba(34,197,94,0.3);color:#4ade80">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="dash-kpi-foot">
                <span>Lifetime Net: <b class="<?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></b></span>
                <span class="flex items-center gap-1 text-warning font-semibold"><i class="fa-solid fa-seedling"></i> <?= number_format($genepisBal) ?></span>
            </div>
        </div>

        <!-- 2. Projected Daily Cashflow -->
        <div class="dash-kpi-card">
            <div class="dash-kpi-top">
                <div>
                    <div class="dash-kpi-label">Projected Daily Net</div>
                    <div class="dash-kpi-value <?= $dailyProfit >= 0 ? 'text-success' : 'text-error' ?>">
                        <?= $dailyProfit >= 0 ? '+' : '' ?><?= currency($dailyProfit) ?><span style="font-size:11px;font-weight:600;opacity:0.6">/d</span>
                    </div>
                </div>
                <div class="dash-kpi-icon" style="background:rgba(56,189,248,0.14);border:1px solid rgba(56,189,248,0.3);color:#38bdf8">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="dash-kpi-foot">
                <span class="text-success">In: +<?= currency($cf['total_income']) ?></span>
                <span class="text-error">Out: -<?= currency($cf['total_expenses']) ?></span>
            </div>
        </div>

        <!-- 3. Mountain Traffic & Capacity -->
        <div class="dash-kpi-card">
            <div class="dash-kpi-top">
                <div>
                    <div class="dash-kpi-label">Daily Visitors</div>
                    <div class="dash-kpi-value text-info" id="liveVisitors" data-count="<?= $dailyVisitors ?>" data-lifts="<?= $liftCount ?>" data-slopes="<?= $slopeCount ?>">
                        <?= number_format($dailyVisitors) ?>
                    </div>
                </div>
                <div class="dash-kpi-icon" style="background:rgba(99,102,241,0.16);border:1px solid rgba(99,102,241,0.35);color:#818cf8">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="dash-kpi-foot">
                <span>Lift Cap: <b><?= number_format($totalLiftCapacity ?? 0) ?>/hr</b></span>
                <span>Ticket: <b><?= $cf['ticket_price'] > 0 ? currency($cf['ticket_price']) : 'Not set' ?></b></span>
            </div>
        </div>

        <!-- 4. Alpine Weather & Snowpack -->
        <div class="dash-kpi-card">
            <div class="dash-kpi-top">
                <div>
                    <div class="dash-kpi-label">Mountain Weather</div>
                    <div class="dash-kpi-value">
                        <?= $weather ? ($weather['temp_formatted'] ?? temp((int)($weather['temp'] ?? 0))) : '-' ?>
                    </div>
                </div>
                <div class="dash-kpi-icon" style="background:rgba(245,158,11,0.14);border:1px solid rgba(245,158,11,0.3);color:#fbbf24">
                    <i class="fa-solid <?= $weather ? ($wIcons[$weather['condition_name']] ?? 'fa-cloud') : 'fa-cloud' ?>"></i>
                </div>
            </div>
            <div class="dash-kpi-foot">
                <span class="truncate"><?= esc($weather['condition_name'] ?? 'Clear') ?></span>
                <span>Base: <b><?= $weather ? snow($weather['snow_base'] ?? 0) : '-' ?></b></span>
            </div>
        </div>
    </div>

    <!-- Modular Widget Grid -->
    <div id="widgetContainer" class="widget-grid">
    <?php foreach ($widgets as $w) : ?>
        <?php if (!$w['visible']) continue; ?>
        <?php $info = $availableWidgets[$w['widget_key']] ?? null; if (!$info) continue; ?>
        <?php $sz = $w['size'] ?? 'large'; ?>

        <div class="widget-item widget-<?= $sz ?>" data-key="<?= $w['widget_key'] ?>" data-size="<?= $sz ?>" id="widget-<?= $w['widget_key'] ?>">
            <div class="drag-handle">
                <div class="flex items-center gap-2 text-base-content/80">
                    <i class="fa-solid fa-grip-vertical text-info"></i>
                    <span><?= esc($info['name']) ?></span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="join">
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','small')" class="btn btn-xs join-item size-btn <?= $sz === 'small' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="small">S</button>
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','medium')" class="btn btn-xs join-item size-btn <?= $sz === 'medium' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="medium">M</button>
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','large')" class="btn btn-xs join-item size-btn <?= $sz === 'large' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="large">L</button>
                    </div>
                    <button type="button" onclick="hideWidget('<?= $w['widget_key'] ?>')" class="btn btn-ghost btn-xs text-error ml-1" title="Hide Widget">
                        <i class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>
            </div>

            <div class="widget-content">

            <?php // ===== STATS (DAILY CASHFLOW & FINANCIAL HEALTH) ===== ?>
            <?php if ($w['widget_key'] === 'stats') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-chart-pie text-primary"></i> Financial &amp; Revenue Breakdown</div>
                    <a href="/finances" class="widget-link">Full Ledger <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                </div>
                <?php if ($sz === 'small') : ?>
                    <div class="space-y-2 my-auto">
                        <div class="dash-subcard flex justify-between items-center text-xs">
                            <span class="text-base-content/60">Daily Revenue</span>
                            <b class="text-success font-mono">+<?= currency($cf['total_income']) ?></b>
                        </div>
                        <div class="dash-subcard flex justify-between items-center text-xs">
                            <span class="text-base-content/60">Daily Expenses</span>
                            <b class="text-error font-mono">-<?= currency($cf['total_expenses']) ?></b>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="grid grid-cols-2 <?= $sz === 'large' ? 'md:grid-cols-6' : 'md:grid-cols-3' ?> gap-2.5">
                        <div class="dash-subcard">
                            <div class="text-[11px] text-base-content/50 mb-1"><i class="fa-solid fa-ticket text-success mr-1"></i>Ticket Sales</div>
                            <div class="text-sm font-extrabold font-mono text-success">+<?= currency($cf['ticket_income']) ?><span class="text-[10px] opacity-60">/d</span></div>
                        </div>
                        <div class="dash-subcard">
                            <div class="text-[11px] text-base-content/50 mb-1"><i class="fa-solid fa-hotel text-info mr-1"></i>Buildings &amp; F&amp;B</div>
                            <div class="text-sm font-extrabold font-mono text-info">+<?= currency($cf['building_income']) ?><span class="text-[10px] opacity-60">/d</span></div>
                        </div>
                        <div class="dash-subcard">
                            <div class="text-[11px] text-base-content/50 mb-1"><i class="fa-solid fa-user-tie text-warning mr-1"></i>Staff Payroll</div>
                            <div class="text-sm font-extrabold font-mono text-error">-<?= currency($cf['salaries']) ?><span class="text-[10px] opacity-60">/d</span></div>
                        </div>
                        <div class="dash-subcard">
                            <div class="text-[11px] text-base-content/50 mb-1"><i class="fa-solid fa-gears text-warning mr-1"></i>Ops &amp; Marketing</div>
                            <div class="text-sm font-extrabold font-mono text-error">-<?= currency($cf['equipment_cost'] + $cf['marketing_cost']) ?><span class="text-[10px] opacity-60">/d</span></div>
                        </div>
                        <?php if ($sz === 'large') : ?>
                        <div class="dash-subcard">
                            <div class="text-[11px] text-base-content/50 mb-1"><i class="fa-solid fa-arrow-trend-up text-success mr-1"></i>All-Time Income</div>
                            <div class="text-sm font-extrabold font-mono text-success"><?= currency($finance['total_income'] ?? 0) ?></div>
                        </div>
                        <div class="dash-subcard">
                            <div class="text-[11px] text-base-content/50 mb-1"><i class="fa-solid fa-arrow-trend-down text-error mr-1"></i>All-Time Costs</div>
                            <div class="text-sm font-extrabold font-mono text-error"><?= currency($finance['total_expenses'] ?? 0) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php // ===== OVERVIEW (MOUNTAIN INFRASTRUCTURE & TRAIL HEALTH) ===== ?>
            <?php if ($w['widget_key'] === 'overview') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-mountain-sun text-info"></i> Mountain Infrastructure</div>
                    <div class="flex items-center gap-3">
                        <a href="/grooming" class="widget-link">Grooming</a>
                        <a href="/map" class="widget-link">Trail Map <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                    </div>
                </div>
                <div class="grid grid-cols-2 <?= $sz === 'large' ? 'md:grid-cols-5' : '' ?> gap-2.5">
                    <a href="/map" class="dash-subcard hover:border-info/40 no-underline">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-base-content/60">Open Slopes</span>
                            <i class="fa-solid fa-person-skiing text-success text-xs"></i>
                        </div>
                        <div class="text-xl font-extrabold mt-1"><?= $slopeCount ?> <span class="text-xs font-normal text-base-content/40">/ <?= $totalSlopes ?? $slopeCount ?></span></div>
                    </a>
                    <a href="/map" class="dash-subcard hover:border-warning/40 no-underline">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-base-content/60">Open Lifts</span>
                            <i class="fa-solid fa-elevator text-warning text-xs"></i>
                        </div>
                        <div class="text-xl font-extrabold mt-1"><?= $liftCount ?> <span class="text-xs font-normal text-base-content/40">/ <?= $totalLifts ?? $liftCount ?></span></div>
                    </a>
                    <a href="/staff" class="dash-subcard hover:border-primary/40 no-underline">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-base-content/60">Active Staff</span>
                            <i class="fa-solid fa-users text-info text-xs"></i>
                        </div>
                        <div class="text-xl font-extrabold mt-1"><?= $staffCount ?> <span class="text-xs font-normal text-base-content/40">hired</span></div>
                    </a>
                    <a href="/buildings" class="dash-subcard hover:border-primary/40 no-underline">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-base-content/60">Buildings</span>
                            <i class="fa-solid fa-building text-primary text-xs"></i>
                        </div>
                        <div class="text-xl font-extrabold mt-1"><?= $buildingCount ?> <span class="text-xs font-normal text-base-content/40">built</span></div>
                    </a>
                    <?php if ($sz === 'large') : ?>
                    <a href="/grooming" class="dash-subcard hover:border-success/40 no-underline col-span-2 md:col-span-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-base-content/60">Trail Grooming</span>
                            <span class="text-xs font-mono font-bold <?= ($avgSlopeCondition ?? 100) >= 70 ? 'text-success' : 'text-warning' ?>"><?= $avgSlopeCondition ?? 100 ?>%</span>
                        </div>
                        <progress class="progress <?= ($avgSlopeCondition ?? 100) >= 70 ? 'progress-success' : 'progress-warning' ?> w-full" value="<?= $avgSlopeCondition ?? 100 ?>" max="100"></progress>
                        <div class="text-[10px] text-base-content/50 mt-1">
                            <?= ($ungroomedCount ?? 0) > 0 ? ($ungroomedCount . ' trail(s) need grooming') : 'All trails in good shape' ?>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif ?>

            <?php // ===== SEASON ===== ?>
            <?php if ($w['widget_key'] === 'season') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-calendar-check text-info"></i> Season <?= $seasonNum ?> Timeline</div>
                    <span class="badge badge-info badge-sm font-mono">Day <?= $seasonProgress ?> / <?= $seasonLength ?> (<?= $seasonPct ?>%)</span>
                </div>
                <?php if ($sz === 'small') : ?>
                    <div class="widget-center">
                        <div class="radial-progress text-primary font-bold" style="--value:<?= $seasonPct ?>;--size:4.25rem;--thickness:5px;" role="progressbar"><?= $seasonPct ?>%</div>
                        <div class="text-xs text-base-content/60 mt-2">Day <?= $seasonProgress ?> of <?= $seasonLength ?></div>
                    </div>
                <?php else : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="dash-subcard">
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-snowflake text-info"></i> Winter Peak Season</span>
                                <span class="font-mono text-base-content/60"><?= min($seasonProgress, 100) ?> / 100 days</span>
                            </div>
                            <progress class="progress progress-info w-full" value="<?= min($seasonProgress, 100) ?>" max="100"></progress>
                        </div>
                        <div class="dash-subcard">
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="font-bold flex items-center gap-1.5"><i class="fa-solid fa-sun text-warning"></i> Off-Season / Summer</span>
                                <span class="font-mono text-base-content/60"><?= max(0, $seasonProgress - 100) ?> / 35 days</span>
                            </div>
                            <progress class="progress progress-warning w-full" value="<?= max(0, $seasonProgress - 100) ?>" max="35"></progress>
                        </div>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php // ===== WEATHER ===== ?>
            <?php if ($w['widget_key'] === 'weather_mini' && $weather) : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-cloud-sun-rain text-warning"></i> Alpine Weather &amp; Forecast</div>
                    <a href="/weather" class="widget-link">Weather Center <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                </div>
                <?php if ($sz === 'small') : ?>
                    <div class="widget-center">
                        <i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?> text-3xl"></i>
                        <div class="text-xl font-bold mt-1.5"><?= $weather['temp_formatted'] ?></div>
                        <div class="text-xs text-base-content/60"><?= esc($weather['condition_name']) ?></div>
                    </div>
                <?php else : ?>
                    <div class="flex flex-wrap items-center justify-between gap-3 my-auto">
                        <div class="flex items-center gap-3.5">
                            <div class="dash-kpi-icon" style="width:46px;height:46px;font-size:22px;background:rgba(56,189,248,0.12);border:1px solid rgba(56,189,248,0.28)">
                                <i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?>"></i>
                            </div>
                            <div>
                                <div class="text-xl font-extrabold"><?= $weather['temp_formatted'] ?> <span class="text-xs font-semibold text-base-content/60">· <?= esc($weather['condition_name']) ?></span></div>
                                <div class="text-xs text-base-content/60 mt-0.5">
                                    Wind: <b><?= speed((int)$weather['wind']) ?></b> · Snowfall: <b><?= snow($weather['snowfall'] ?? 0) ?></b> · Base: <b><?= snow($weather['snow_base'] ?? 0) ?></b>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($forecast)) : ?>
                        <div class="flex gap-2">
                            <?php foreach (array_slice($forecast, 0, $sz === 'large' ? 5 : 3) as $i => $fc) : ?>
                            <div class="dash-subcard text-center px-2.5 py-1.5 min-w-[54px]">
                                <div class="text-[10px] text-base-content/50 font-semibold">+<?= $i + 1 ?>d</div>
                                <i class="fa-solid <?= $wIcons[$fc['condition']] ?? 'fa-cloud' ?> text-xs my-1"></i>
                                <div class="text-xs font-bold font-mono"><?= temp($fc['temp']) ?></div>
                            </div>
                            <?php endforeach ?>
                        </div>
                        <?php endif ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php // ===== QUICK ACTIONS (OPERATIONS COMMAND DECK) ===== ?>
            <?php if ($w['widget_key'] === 'actions') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-bolt text-warning"></i> Operations Command Deck</div>
                </div>
                <div class="grid grid-cols-2 <?= $sz === 'large' ? 'sm:grid-cols-4 md:grid-cols-6' : ($sz === 'medium' ? 'sm:grid-cols-3' : '') ?> gap-2">
                    <a href="/map" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(56,189,248,0.16);color:#38bdf8"><i class="fa-solid fa-map-location-dot"></i></span>
                        <span>Trail Map</span>
                    </a>
                    <a href="/resort" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(34,197,94,0.16);color:#4ade80"><i class="fa-solid fa-mountain-sun"></i></span>
                        <span>Resort Ops</span>
                    </a>
                    <a href="/finances" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(245,158,11,0.16);color:#fbbf24"><i class="fa-solid fa-coins"></i></span>
                        <span>Finances</span>
                    </a>
                    <a href="/staff" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(99,102,241,0.18);color:#818cf8"><i class="fa-solid fa-users"></i></span>
                        <span>Staff (<?= $staffCount ?>)</span>
                    </a>
                    <a href="/grooming" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(20,184,166,0.16);color:#2dd4bf"><i class="fa-solid fa-tractor"></i></span>
                        <span>Grooming</span>
                    </a>
                    <a href="/snowmaking" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(59,130,246,0.16);color:#60a5fa"><i class="fa-solid fa-snowflake"></i></span>
                        <span>Snowmaking</span>
                    </a>
                    <a href="/buildings" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(168,85,247,0.16);color:#c084fc"><i class="fa-solid fa-hotel"></i></span>
                        <span>Buildings</span>
                    </a>
                    <a href="/terrain-parks" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(236,72,153,0.16);color:#f472b6"><i class="fa-solid fa-person-snowboarding"></i></span>
                        <span>Terrain Parks</span>
                    </a>
                    <a href="/parking" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(148,163,184,0.16);color:#cbd5e1"><i class="fa-solid fa-square-parking"></i></span>
                        <span>Parking</span>
                    </a>
                    <a href="/equipment" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(251,146,60,0.16);color:#fb923c"><i class="fa-solid fa-toolbox"></i></span>
                        <span>Equipment</span>
                    </a>
                    <a href="/marketing" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(244,63,94,0.16);color:#fb7185"><i class="fa-solid fa-bullhorn"></i></span>
                        <span>Marketing</span>
                    </a>
                    <a href="/vote" class="dash-action-tile">
                        <span class="dash-action-icon" style="background:rgba(56,189,248,0.16);color:#38bdf8"><i class="fa-solid fa-check-to-slot"></i></span>
                        <span>Resort Vote</span>
                    </a>
                </div>
            <?php endif ?>

            <?php // ===== ACHIEVEMENT ALERTS (MILESTONE REWARDS SUMMARY) ===== ?>
            <?php if ($w['widget_key'] === 'achievements_mini') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-trophy text-warning"></i> Milestone Rewards</div>
                    <a href="/achievements" class="widget-link">Rewards Page <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                </div>
                <?php if ($unclaimedAchievements > 0) : ?>
                    <div class="dash-subcard flex items-center justify-between gap-3 my-auto" style="border-color:rgba(245,158,11,0.4);background:rgba(245,158,11,0.1)">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-award text-warning text-2xl"></i>
                            <div>
                                <div class="text-sm font-extrabold"><?= $unclaimedAchievements ?> Unclaimed Reward<?= $unclaimedAchievements > 1 ? 's' : '' ?></div>
                                <div class="text-xs text-base-content/60"><?= $completedAchievements ?? 0 ?> total milestones unlocked</div>
                            </div>
                        </div>
                        <a href="/achievements" class="btn btn-warning btn-sm font-bold">Claim Now</a>
                    </div>
                <?php else : ?>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 my-auto">
                        <div class="dash-subcard flex items-center gap-3">
                            <i class="fa-solid fa-medal text-warning text-xl"></i>
                            <div>
                                <div class="text-lg font-extrabold"><?= $completedAchievements ?? 0 ?></div>
                                <div class="text-[11px] text-base-content/50">Completed Milestones</div>
                            </div>
                        </div>
                        <div class="dash-subcard flex items-center gap-3">
                            <i class="fa-solid fa-hourglass-half text-info text-xl"></i>
                            <div>
                                <div class="text-lg font-extrabold"><?= count($inProgressAchievements) ?></div>
                                <div class="text-[11px] text-base-content/50">Active Next Goals</div>
                            </div>
                        </div>
                        <div class="dash-subcard flex items-center justify-between gap-2">
                            <div>
                                <div class="text-xs font-bold text-success">All Rewards Claimed</div>
                                <div class="text-[11px] text-base-content/50">Keep expanding your resort</div>
                            </div>
                            <a href="/achievements" class="btn btn-ghost btn-xs border border-white/10">View</a>
                        </div>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php // ===== ACHIEVEMENTS (NEXT GOALS PROGRESS) ===== ?>
            <?php if ($w['widget_key'] === 'achievements' || $w['widget_key'] === 'getting_started') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-award text-warning"></i> Next Achievement Goals</div>
                    <a href="/achievements" class="widget-link">View All <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                </div>
                <?php if (empty($inProgressAchievements)) : ?>
                    <div class="widget-center text-sm text-base-content/40">All achievements completed!</div>
                <?php else : ?>
                    <div class="space-y-2 flex-1 overflow-y-auto">
                        <?php foreach (array_slice($inProgressAchievements, 0, 4) as $ach) : ?>
                        <?php $pct = $ach['target'] > 0 ? min(100, (int) round($ach['progress'] / $ach['target'] * 100)) : 0; ?>
                        <div class="dash-subcard py-2 px-3 flex items-center gap-3">
                            <i class="<?= esc($ach['icon'] ?: 'fa-solid fa-star') ?> text-warning text-sm w-4 text-center"></i>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center text-xs mb-1">
                                    <span class="font-bold truncate"><?= esc($ach['name']) ?></span>
                                    <span class="font-mono text-[11px] text-base-content/60"><?= number_format($ach['progress']) ?> / <?= number_format($ach['target']) ?> (<?= $pct ?>%)</span>
                                </div>
                                <progress class="progress progress-primary w-full" value="<?= $ach['progress'] ?>" max="<?= max(1, $ach['target']) ?>"></progress>
                            </div>
                        </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php // ===== ACTIVITY (LIVE OPERATIONS LOG) ===== ?>
            <?php if ($w['widget_key'] === 'activity') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-clock-rotate-left text-info"></i> Recent Resort Activity</div>
                    <a href="/activity" class="widget-link">Full Log <i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                </div>
                <?php if (empty($recentActivity)) : ?>
                    <div class="widget-center text-sm text-base-content/40">No recent activity logged</div>
                <?php else : ?>
                    <div id="activityFeedList" class="space-y-1.5 flex-1 overflow-y-auto">
                        <?php foreach (array_slice($recentActivity, 0, 6) as $log) : ?>
                        <div class="dash-subcard py-1.5 px-2.5 flex items-center gap-2.5 text-xs">
                            <i class="<?= esc($log['icon'] ?? 'fa-solid fa-circle-dot') ?> text-info w-4 text-center"></i>
                            <span class="flex-1 truncate font-medium"><?= esc($log['message']) ?></span>
                            <span class="badge badge-ghost badge-xs font-mono">D<?= esc($log['game_day'] ?? $gameDay) ?></span>
                        </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php // ===== PARKING ===== ?>
            <?php if ($w['widget_key'] === 'parking_mini') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-square-parking text-info"></i> Parking Capacity</div>
                    <a href="/parking" class="widget-link">Manage</a>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center my-auto">
                    <div class="dash-subcard"><div class="text-base font-extrabold"><?= count($parkingFacilities) ?></div><div class="text-[10px] text-base-content/50">Lots</div></div>
                    <div class="dash-subcard"><div class="text-base font-extrabold"><?= number_format($totalParkingCap) ?></div><div class="text-[10px] text-base-content/50">Spots</div></div>
                    <div class="dash-subcard"><div class="text-base font-extrabold <?= $parkingPct > 90 ? 'text-error' : ($parkingPct > 70 ? 'text-warning' : 'text-success') ?>"><?= $parkingPct ?>%</div><div class="text-[10px] text-base-content/50">Occupied</div></div>
                </div>
                <progress class="progress progress-info w-full mt-2.5" value="<?= $totalOccupied ?>" max="<?= max(1, $totalParkingCap) ?>"></progress>
            <?php endif ?>

            <?php // ===== TERRAIN PARKS ===== ?>
            <?php if ($w['widget_key'] === 'terrain_parks_mini') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-person-snowboarding text-warning"></i> Terrain Parks</div>
                    <a href="/terrain-parks" class="widget-link">Manage</a>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center my-auto">
                    <div class="dash-subcard"><div class="text-base font-extrabold text-success"><?= count($openParks) ?></div><div class="text-[10px] text-base-content/50">Open</div></div>
                    <div class="dash-subcard"><div class="text-base font-extrabold text-warning"><?= count($buildingParks) ?></div><div class="text-[10px] text-base-content/50">Building</div></div>
                    <div class="dash-subcard"><div class="text-base font-extrabold text-info"><?= number_format($totalParkVisitors) ?></div><div class="text-[10px] text-base-content/50">Riders</div></div>
                </div>
            <?php endif ?>

            <?php // ===== FINANCES MINI ===== ?>
            <?php if ($w['widget_key'] === 'finances_mini' && $finance) : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-coins text-warning"></i> All-Time Finances</div>
                    <a href="/finances" class="widget-link">Details</a>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center my-auto">
                    <div class="dash-subcard"><div class="font-bold text-success text-xs font-mono"><?= currency($finance['total_income'] ?? 0) ?></div><div class="text-[10px] text-base-content/50 mt-0.5">Income</div></div>
                    <div class="dash-subcard"><div class="font-bold text-error text-xs font-mono"><?= currency($finance['total_expenses'] ?? 0) ?></div><div class="text-[10px] text-base-content/50 mt-0.5">Expenses</div></div>
                    <div class="dash-subcard"><div class="font-bold text-xs font-mono <?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></div><div class="text-[10px] text-base-content/50 mt-0.5">Net</div></div>
                </div>
            <?php endif ?>

            <?php // ===== STAFF MINI ===== ?>
            <?php if ($w['widget_key'] === 'staff_mini') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-users text-info"></i> Staff &amp; Morale</div>
                    <a href="/staff" class="widget-link">Manage</a>
                </div>
                <?php if (empty($staffAll)) : ?>
                    <div class="widget-center text-xs text-base-content/50">
                        <p>No staff hired yet</p>
                        <a href="/staff/hire" class="btn btn-xs btn-primary mt-2">Hire Staff</a>
                    </div>
                <?php else : ?>
                    <div class="grid grid-cols-2 gap-1.5 text-xs mb-2">
                        <?php foreach ($roles as $role => $count) : ?>
                        <div class="dash-subcard py-1.5 px-2.5 flex justify-between">
                            <span class="text-base-content/70 truncate capitalize"><?= esc(str_replace('_', ' ', $role)) ?></span>
                            <span class="font-bold"><?= $count ?></span>
                        </div>
                        <?php endforeach ?>
                    </div>
                    <div class="flex items-center gap-2 mt-auto pt-1 text-xs">
                        <span class="text-base-content/60">Morale</span>
                        <progress class="progress <?= $avgMorale >= 60 ? 'progress-success' : ($avgMorale >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1" value="<?= $avgMorale ?>" max="100"></progress>
                        <span class="font-mono font-bold"><?= $avgMorale ?>%</span>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php // ===== EQUIPMENT MINI ===== ?>
            <?php if ($w['widget_key'] === 'equipment_mini') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-toolbox text-warning"></i> Equipment</div>
                    <a href="/equipment" class="widget-link">Fleet</a>
                </div>
                <div class="widget-center">
                    <div class="text-2xl font-extrabold"><?= count($equipment) ?></div>
                    <div class="text-xs text-base-content/50">units in fleet</div>
                </div>
            <?php endif ?>

            <?php // ===== INSURANCE MINI ===== ?>
            <?php if ($w['widget_key'] === 'insurance_mini') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-shield-halved text-success"></i> Insurance</div>
                    <a href="/insurance" class="widget-link">Policies</a>
                </div>
                <div class="grid grid-cols-2 gap-2 text-center my-auto">
                    <div class="dash-subcard"><div class="text-lg font-extrabold text-success"><?= count($activeIns) ?></div><div class="text-[10px] text-base-content/50">Active</div></div>
                    <div class="dash-subcard"><div class="text-lg font-extrabold"><?= max(0, count($insurance) - count($activeIns)) ?></div><div class="text-[10px] text-base-content/50">Available</div></div>
                </div>
            <?php endif ?>

            <?php // ===== LOANS MINI ===== ?>
            <?php if ($w['widget_key'] === 'loans_mini') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-landmark text-primary"></i> Bank Loans</div>
                    <a href="/bank" class="widget-link">Bank</a>
                </div>
                <?php if (empty($loans)) : ?>
                    <div class="widget-center text-xs text-success font-semibold"><i class="fa-solid fa-circle-check mb-1 text-base"></i>Debt-Free</div>
                <?php else : ?>
                    <div class="widget-center">
                        <div class="text-lg font-extrabold text-error font-mono"><?= currency(array_sum(array_column($loans, 'remaining'))) ?></div>
                        <div class="text-[11px] text-base-content/50"><?= count($loans) ?> active loan<?= count($loans) > 1 ? 's' : '' ?></div>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php // ===== MARKETING MINI ===== ?>
            <?php if ($w['widget_key'] === 'marketing_mini') : ?>
                <div class="widget-header">
                    <div class="widget-title"><i class="fa-solid fa-bullhorn text-secondary"></i> Marketing</div>
                    <a href="/marketing" class="widget-link">Campaigns</a>
                </div>
                <?php if (empty($marketing)) : ?>
                    <div class="widget-center text-xs text-base-content/50">No active campaigns</div>
                <?php else : ?>
                    <div class="widget-center">
                        <div class="text-2xl font-extrabold text-success"><?= count($marketing) ?></div>
                        <div class="text-xs text-base-content/50">active campaign<?= count($marketing) > 1 ? 's' : '' ?></div>
                    </div>
                <?php endif ?>
            <?php endif ?>

            </div><!-- /widget-content -->
        </div><!-- /widget-item -->
    <?php endforeach ?>
    </div>

    <!-- Hidden Widgets Drawer (Shown in Customize Mode) -->
    <div id="hiddenWidgets" class="hidden mt-6 p-4 rounded-xl bg-base-100/80 border border-white/10">
        <h3 class="text-xs font-bold uppercase tracking-wider text-base-content/60 mb-3">
            <i class="fa-solid fa-eye-slash mr-1"></i> Hidden Widgets — Click to Add to Dashboard
        </h3>
        <div class="flex flex-wrap gap-2" id="hiddenWidgetList">
        <?php foreach ($widgets as $w) : ?>
            <?php if ($w['visible']) continue; $info = $availableWidgets[$w['widget_key']] ?? null; if (!$info) continue; ?>
            <button type="button" onclick="showWidget('<?= $w['widget_key'] ?>')" class="btn btn-outline btn-sm gap-1.5 hidden-widget-btn" data-key="<?= $w['widget_key'] ?>">
                <i class="<?= $info['icon'] ?> text-xs"></i> <?= esc($info['name']) ?> <i class="fa-solid fa-plus text-xs text-success"></i>
            </button>
        <?php endforeach ?>
        </div>
    </div>
</div>

<script>
const csrfName = '<?= csrf_token() ?>';
let csrfHash = '<?= csrf_hash() ?>';
const dashRoot = document.getElementById('dashRoot');
const container = document.getElementById('widgetContainer');
const editBtn = document.getElementById('editModeBtn');
const hiddenSection = document.getElementById('hiddenWidgets');
let editMode = false, dragEl = null;

function toggleEditMode() {
    editMode = !editMode;
    container.classList.toggle('edit-mode', editMode);
    dashRoot.classList.toggle('edit-mode-active', editMode);
    hiddenSection.classList.toggle('hidden', !editMode);
    editBtn.innerHTML = editMode
        ? '<i class="fa-solid fa-check"></i> Done'
        : '<i class="fa-solid fa-table-columns"></i> Customize';
    editBtn.classList.toggle('btn-primary', editMode);
    editBtn.classList.toggle('btn-ghost', !editMode);
    container.querySelectorAll('.widget-item').forEach(el => el.setAttribute('draggable', editMode ? 'true' : 'false'));
}

function ajaxPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfHash
        },
        body: JSON.stringify({ ...data, [csrfName]: csrfHash })
    })
    .then(r => r.json())
    .then(json => {
        if (json && json.csrf_hash) csrfHash = json.csrf_hash;
        return json;
    });
}

function hideWidget(key) {
    const el = document.getElementById('widget-' + key);
    if (el) {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 180);
    }
    ajaxPost('/dashboard/toggle-widget', { widget_key: key });
    const list = document.getElementById('hiddenWidgetList');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline btn-sm gap-1.5 hidden-widget-btn';
    btn.dataset.key = key;
    btn.innerHTML = '<i class="fa-solid fa-plus text-xs text-success"></i> ' + key.replace(/_/g, ' ');
    btn.onclick = () => showWidget(key);
    list.appendChild(btn);
}

function showWidget(key) {
    ajaxPost('/dashboard/toggle-widget', { widget_key: key }).then(() => {
        sessionStorage.setItem('editMode', '1');
        location.reload();
    });
}

function resizeWidget(key, size) {
    const el = document.getElementById('widget-' + key);
    if (el) {
        el.classList.remove('widget-small', 'widget-medium', 'widget-large');
        el.classList.add('widget-' + size);
        el.dataset.size = size;
        el.querySelectorAll('.size-btn').forEach(b => {
            b.classList.toggle('btn-primary', b.dataset.sz === size);
            b.classList.toggle('btn-ghost', b.dataset.sz !== size);
        });
    }
    ajaxPost('/dashboard/resize-widget', { widget_key: key, size: size });
}

function saveOrder() {
    const order = [...container.querySelectorAll('.widget-item')].map(el => el.dataset.key);
    ajaxPost('/dashboard/reorder-widgets', { order });
}

container.addEventListener('dragstart', e => {
    if (!editMode) return;
    dragEl = e.target.closest('.widget-item');
    if (dragEl) {
        dragEl.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
    }
});
container.addEventListener('dragend', () => {
    if (dragEl) dragEl.classList.remove('dragging');
    container.querySelectorAll('.widget-item').forEach(el => el.classList.remove('drag-over'));
    if (dragEl) saveOrder();
    dragEl = null;
});
container.addEventListener('dragover', e => {
    if (!editMode || !dragEl) return;
    e.preventDefault();
    const t = e.target.closest('.widget-item');
    container.querySelectorAll('.widget-item').forEach(el => el.classList.remove('drag-over'));
    if (t && t !== dragEl) t.classList.add('drag-over');
});
container.addEventListener('drop', e => {
    if (!editMode || !dragEl) return;
    e.preventDefault();
    const t = e.target.closest('.widget-item');
    if (t && t !== dragEl) {
        const items = [...container.querySelectorAll('.widget-item')];
        if (items.indexOf(dragEl) < items.indexOf(t)) container.insertBefore(dragEl, t.nextSibling);
        else container.insertBefore(dragEl, t);
    }
});

if (sessionStorage.getItem('editMode')) {
    sessionStorage.removeItem('editMode');
    toggleEditMode();
}
</script>
<?php if (featureEnabled("live_visitors")) : ?>
<script data-cfasync="false">
(function(){var el=document.getElementById("liveVisitors");if(!el)return;var base=parseInt(el.dataset.count)||0,lifts=parseInt(el.dataset.lifts)||0,slopes=parseInt(el.dataset.slopes)||0;var capacity=Math.max(1,lifts*80+slopes*40);var cur=Math.min(base,capacity);el.textContent=cur.toLocaleString();var nv=document.getElementById("navVisitors");if(nv)nv.textContent=cur.toLocaleString()+" visitors";function tick(){if(lifts===0&&slopes===0){el.textContent="0";setTimeout(tick,10000);return}var range=Math.max(1,Math.ceil(capacity*0.02));var d=Math.floor(Math.random()*(range*2+1))-range;if(Math.random()<0.4)d=0;cur=Math.max(0,Math.min(cur+d,capacity));el.textContent=cur.toLocaleString();var nv2=document.getElementById("navVisitors");if(nv2)nv2.textContent=cur.toLocaleString()+" visitors";if(d!==0){el.style.transition="color 0.5s";el.style.color=d>0?"rgb(34,197,94)":"rgb(248,113,113)";setTimeout(function(){el.style.color=""},1500)}setTimeout(tick,8000+Math.floor(Math.random()*7000))}setTimeout(tick,5000)})();
</script>
<?php endif ?>
<?php if (featureEnabled("activity_feed")) : ?>
<script data-cfasync="false">
(function(){
    var feedList = document.getElementById("activityFeedList");
    if (!feedList) return;
    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function refresh() {
        fetch("/dashboard/activity", { headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then(function(r) { return r.json(); })
        .then(function(logs) {
            if (!Array.isArray(logs) || !logs.length) return;
            feedList.innerHTML = "";
            logs.slice(0, 6).forEach(function(log) {
                var div = document.createElement("div");
                div.className = "dash-subcard py-1.5 px-2.5 flex items-center gap-2.5 text-xs";
                div.innerHTML = '<i class="' + esc(log.icon || 'fa-solid fa-circle-dot') + ' text-info w-4 text-center"></i>' +
                    '<span class="flex-1 truncate font-medium">' + esc(log.message) + '</span>' +
                    '<span class="badge badge-ghost badge-xs font-mono">D' + esc(log.game_day || '') + '</span>';
                feedList.appendChild(div);
            });
        }).catch(function(){});
    }
    setInterval(refresh, 30000);
})();
</script>
<?php endif ?>
<?= $this->endSection() ?>
