<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
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
.dash { max-width: 1240px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
@media (min-width: 1024px) { .dash { padding: 2rem 2rem 3rem; } }

.dash-head {
    display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;
    gap: 1rem; padding-bottom: 1.25rem; margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--color-base-300);
}
.dash-head h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.015em; line-height: 1.2; margin: 0; }
.dash-meta { font-size: 0.8125rem; color: color-mix(in oklab, var(--color-base-content) 62%, transparent); margin-top: 0.3rem; }
.dash-meta .sep { margin: 0 0.45em; opacity: 0.45; }
.status-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: currentColor; margin-right: 0.35em; position: relative; top: -1px; }
.dash-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }

.num { font-variant-numeric: tabular-nums; }
.muted { color: color-mix(in oklab, var(--color-base-content) 60%, transparent); }

/* Headline numbers: one flat strip, no icons */
.kpis {
    display: grid; grid-template-columns: repeat(2, 1fr);
    background: var(--color-base-100); border: 1px solid var(--color-base-300);
    border-radius: 8px; margin-bottom: 1.5rem;
}
@media (min-width: 1024px) { .kpis { grid-template-columns: repeat(4, 1fr); } }
.kpi { padding: 1rem 1.25rem; min-width: 0; }
.kpi:nth-child(even) { border-left: 1px solid var(--color-base-300); }
.kpi:nth-child(n+3) { border-top: 1px solid var(--color-base-300); }
@media (min-width: 1024px) {
    .kpi:nth-child(n+3) { border-top: 0; }
    .kpi:nth-child(n+2) { border-left: 1px solid var(--color-base-300); }
}
.kpi-label { font-size: 0.75rem; color: color-mix(in oklab, var(--color-base-content) 60%, transparent); }
.kpi-value { font-size: 1.5rem; font-weight: 650; line-height: 1.25; margin-top: 0.15rem; letter-spacing: -0.01em; font-variant-numeric: tabular-nums; }
.kpi-value small { font-size: 0.75rem; font-weight: 500; opacity: 0.55; margin-left: 1px; }
.kpi-sub { font-size: 0.75rem; color: color-mix(in oklab, var(--color-base-content) 60%, transparent); margin-top: 0.35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Customize banner */
.edit-mode-banner {
    display: none; align-items: center; justify-content: space-between; gap: 12px;
    padding: 0.6rem 0.9rem; margin-bottom: 1rem; border-radius: 8px; font-size: 0.8125rem;
    background: color-mix(in oklab, var(--color-primary) 10%, transparent);
    border: 1px solid color-mix(in oklab, var(--color-primary) 35%, transparent);
}
.edit-mode-active .edit-mode-banner { display: flex; }

/* Widget grid */
.widget-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
@media (max-width: 1024px) { .widget-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .widget-grid { grid-template-columns: 1fr; } }
.widget-small { grid-column: span 1; }
.widget-medium { grid-column: span 2; }
.widget-large { grid-column: span 4; }
@media (max-width: 1024px) { .widget-large { grid-column: span 2; } }
@media (max-width: 640px) { .widget-small, .widget-medium, .widget-large { grid-column: span 1; } }

.widget-item {
    container-type: inline-size;
    background: var(--color-base-100); border: 1px solid var(--color-base-300);
    border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;
}
.widget-grid > .widget-item:nth-child(n+5) { content-visibility: auto; contain-intrinsic-size: auto none auto 180px; }

/* Size-aware layouts */
.cq-compact { display: block; }
.cq-expanded { display: none; }
.cq-large-only { display: none; }
.cq-stats-grid, .cq-overview-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem 1.25rem; }
.cq-actions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0 1rem; }
.cq-season-grid { display: grid; grid-template-columns: 1fr; gap: 0.875rem; }
.cq-ach-mini-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }

@media (min-width: 640px) {
    .widget-medium .cq-compact, .widget-large .cq-compact { display: none; }
    .widget-medium .cq-expanded, .widget-large .cq-expanded { display: block; }
    .widget-large .cq-large-only { display: block; }
    .widget-medium .cq-stats-grid { grid-template-columns: repeat(4, 1fr); }
    .widget-large .cq-stats-grid { grid-template-columns: repeat(6, 1fr); }
    .widget-large .cq-overview-grid { grid-template-columns: repeat(5, 1fr); }
    .widget-medium .cq-season-grid, .widget-large .cq-season-grid { grid-template-columns: repeat(2, 1fr); }
    .widget-medium .cq-actions-grid { grid-template-columns: repeat(3, 1fr); }
    .widget-large .cq-actions-grid { grid-template-columns: repeat(6, 1fr); }
    .widget-medium .cq-ach-mini-grid, .widget-large .cq-ach-mini-grid { grid-template-columns: repeat(3, 1fr); }
}
@supports (container-type: inline-size) {
    @container (min-width: 360px) { .cq-compact { display: none; } .cq-expanded { display: block; } }
    @container (min-width: 440px) { .cq-actions-grid { grid-template-columns: repeat(3, 1fr); } .cq-ach-mini-grid { grid-template-columns: repeat(3, 1fr); } }
    @container (min-width: 480px) { .cq-stats-grid { grid-template-columns: repeat(4, 1fr); } .cq-season-grid { grid-template-columns: repeat(2, 1fr); } }
    @container (min-width: 720px) {
        .cq-large-only { display: block; }
        .cq-stats-grid { grid-template-columns: repeat(6, 1fr); }
        .cq-overview-grid { grid-template-columns: repeat(5, 1fr); }
        .cq-actions-grid { grid-template-columns: repeat(6, 1fr); }
    }
}

.widget-content { padding: 1rem 1.25rem 1.125rem; flex: 1; display: flex; flex-direction: column; min-height: 0; }
.widget-header { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.875rem; }
.widget-title { font-size: 0.875rem; font-weight: 600; margin: 0; }
.widget-title .aside { font-weight: 400; margin-left: 0.4em; color: color-mix(in oklab, var(--color-base-content) 55%, transparent); }
.widget-links { display: flex; gap: 0.875rem; }
.widget-link { font-size: 0.75rem; white-space: nowrap; text-decoration: none; color: color-mix(in oklab, var(--color-base-content) 60%, transparent); }
.widget-link:hover { color: var(--color-base-content); text-decoration: underline; }
.widget-center { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 0.5rem 0; }

/* Plain label-over-number stats */
.stat { display: block; min-width: 0; color: inherit; text-decoration: none; }
a.stat:hover .stat-value { text-decoration: underline; text-underline-offset: 3px; }
.stat-label { font-size: 0.75rem; color: color-mix(in oklab, var(--color-base-content) 60%, transparent); }
.stat-value { font-size: 1.125rem; font-weight: 600; margin-top: 0.1rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
.stat-value small { font-size: 0.75rem; font-weight: 400; color: color-mix(in oklab, var(--color-base-content) 50%, transparent); }
.stat-note { font-size: 0.6875rem; margin-top: 0.3rem; color: color-mix(in oklab, var(--color-base-content) 55%, transparent); }

/* List rows separated by rules, not boxes */
.dash-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; font-size: 0.8125rem; border-top: 1px solid var(--color-base-300); }
.dash-row:first-child { border-top: 0; padding-top: 0; }
.dash-row .day { font-size: 0.6875rem; font-variant-numeric: tabular-nums; color: color-mix(in oklab, var(--color-base-content) 50%, transparent); }
.dash-row > i { width: 1rem; text-align: center; opacity: 0.55; }

.dash-action-tile {
    display: flex; align-items: center; gap: 0.6rem; padding: 0.45rem 0.4rem; margin: 0 -0.4rem;
    font-size: 0.8125rem; color: inherit; text-decoration: none; border-radius: 6px;
}
.dash-action-tile i { width: 1rem; text-align: center; opacity: 0.5; }
.dash-action-tile:hover { background: color-mix(in oklab, var(--color-base-content) 6%, transparent); }

.forecast { display: flex; }
.forecast > div { padding: 0 0.75rem; text-align: center; border-left: 1px solid var(--color-base-300); }
.forecast > div:first-child { border-left: 0; }

/* Customize mode */
.drag-handle {
    display: none; align-items: center; justify-content: space-between;
    padding: 6px 10px; font-size: 0.75rem; cursor: grab; user-select: none;
    background: var(--color-base-200); border-bottom: 1px solid var(--color-base-300);
}
.drag-handle:active { cursor: grabbing; }
.edit-mode .drag-handle { display: flex !important; }
.edit-mode .widget-item { outline: 1px dashed color-mix(in oklab, var(--color-primary) 50%, transparent); outline-offset: 2px; }
.widget-item.dragging { opacity: 0.35; }
.widget-item.drag-over { outline: 2px solid var(--color-primary) !important; }
</style>

<div class="dash" id="dashRoot">

    <header class="dash-head">
        <div>
            <h1><?= esc($resortMapName ?? 'Park City') ?></h1>
            <p class="dash-meta">
                <span class="<?= $resortOpen ? 'text-success' : 'text-error' ?>"><span class="status-dot"></span><?= $resortOpen ? 'Open' : 'Closed' ?></span><span class="sep">·</span>Season <?= $seasonNum ?>, day <?= $gameDay ?><span class="sep">·</span><?= $liftCount ?> lift<?= $liftCount === 1 ? '' : 's' ?>, <?= $slopeCount ?> slope<?= $slopeCount === 1 ? '' : 's' ?> running<span class="sep">·</span><span class="num"><?= number_format($repScore) ?></span> reputation
            </p>
        </div>
        <div class="dash-actions">
            <?php if ($bonusAvailable): ?>
            <a href="/daily-bonus" class="btn btn-warning btn-sm">Claim daily bonus</a>
            <?php endif; ?>
            <a href="/map" class="btn btn-primary btn-sm">Trail map</a>
            <a href="/resort" class="btn btn-ghost btn-sm">Resort settings</a>
            <button id="editModeBtn" type="button" onclick="toggleEditMode()" aria-pressed="false" class="btn btn-ghost btn-sm">Customize</button>
        </div>
    </header>

    <div class="edit-mode-banner">
        <span>Drag a widget by its top bar to reorder it. Use S / M / L to change its width, or the eye icon to hide it.</span>
        <button type="button" onclick="toggleEditMode()" class="btn btn-xs btn-primary">Done</button>
    </div>

    <?php if (!empty($alerts) || $unclaimedAchievements > 0) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-4">
        <?php foreach ($alerts as $alert) : ?>
        <a href="<?= $alert['link'] ?>" class="alert alert-<?= $alert['type'] ?> py-2 px-3.5 text-sm flex items-center justify-between">
            <span class="flex items-center gap-2.5"><i class="<?= $alert['icon'] ?>"></i><?= esc($alert['msg']) ?></span>
            <span class="underline whitespace-nowrap ml-2">Fix</span>
        </a>
        <?php endforeach ?>
        <?php if ($unclaimedAchievements > 0): ?>
        <a href="/achievements" class="alert alert-warning py-2 px-3.5 text-sm flex items-center justify-between">
            <span><?= $unclaimedAchievements ?> achievement reward<?= $unclaimedAchievements > 1 ? 's' : '' ?> to claim</span>
            <span class="underline whitespace-nowrap ml-2">Claim</span>
        </a>
        <?php endif; ?>
    </div>
    <?php endif ?>

    <section class="kpis" aria-label="Key numbers">
        <div class="kpi">
            <div class="kpi-label">Cash</div>
            <div class="kpi-value <?= $cashBalance < 0 ? 'text-error' : '' ?>"><?= currency($cashBalance) ?></div>
            <div class="kpi-sub">Lifetime <span class="<?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></span> · <?= number_format($genepisBal) ?> genepis</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Net per day</div>
            <div class="kpi-value <?= $dailyProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $dailyProfit >= 0 ? '+' : '' ?><?= currency($dailyProfit) ?></div>
            <div class="kpi-sub"><?= currency($cf['total_income']) ?> in · <?= currency($cf['total_expenses']) ?> out</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Visitors today</div>
            <div class="kpi-value" id="liveVisitors" data-count="<?= $dailyVisitors ?>" data-lifts="<?= $liftCount ?>" data-slopes="<?= $slopeCount ?>"><?= number_format($dailyVisitors) ?></div>
            <div class="kpi-sub">Ticket <?= $cf['ticket_price'] > 0 ? currency($cf['ticket_price']) : 'not set' ?> · lifts <?= number_format($totalLiftCapacity ?? 0) ?>/hr</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Weather</div>
            <div class="kpi-value"><?= $weather ? ($weather['temp_formatted'] ?? temp((int)($weather['temp'] ?? 0))) : '–' ?></div>
            <div class="kpi-sub"><?= esc($weather['condition_name'] ?? 'Clear') ?> · <?= $weather ? snow($weather['snow_base'] ?? 0) : '–' ?> base</div>
        </div>
    </section>

    <div id="widgetContainer" class="widget-grid">
    <?php foreach ($widgets as $w) : ?>
        <?php if (!$w['visible']) continue; ?>
        <?php $info = $availableWidgets[$w['widget_key']] ?? null; if (!$info) continue; ?>
        <?php $sz = $w['size'] ?? 'large'; ?>

        <div class="widget-item widget-<?= $sz ?>" data-key="<?= $w['widget_key'] ?>" data-name="<?= esc($info['name']) ?>" data-size="<?= $sz ?>" id="widget-<?= $w['widget_key'] ?>">
            <div class="drag-handle">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-grip-vertical opacity-50" aria-hidden="true"></i>
                    <span><?= esc($info['name']) ?></span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="join" role="group" aria-label="Resize <?= esc($info['name']) ?> widget">
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','small')" aria-label="Resize <?= esc($info['name']) ?> widget to small" aria-pressed="<?= $sz === 'small' ? 'true' : 'false' ?>" class="btn btn-xs join-item size-btn <?= $sz === 'small' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="small">S</button>
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','medium')" aria-label="Resize <?= esc($info['name']) ?> widget to medium" aria-pressed="<?= $sz === 'medium' ? 'true' : 'false' ?>" class="btn btn-xs join-item size-btn <?= $sz === 'medium' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="medium">M</button>
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','large')" aria-label="Resize <?= esc($info['name']) ?> widget to large" aria-pressed="<?= $sz === 'large' ? 'true' : 'false' ?>" class="btn btn-xs join-item size-btn <?= $sz === 'large' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="large">L</button>
                    </div>
                    <button type="button" onclick="hideWidget('<?= $w['widget_key'] ?>')" class="btn btn-ghost btn-xs ml-1" aria-label="Hide <?= esc($info['name']) ?> widget">
                        <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="widget-content">

            <?php if ($w['widget_key'] === 'stats') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Cash flow <span class="aside">per day</span></h2>
                    <a href="/finances" class="widget-link">Ledger →</a>
                </div>
                <div class="cq-compact my-auto">
                    <div class="dash-row"><span class="flex-1 muted">Revenue</span><b class="num text-success">+<?= currency($cf['total_income']) ?></b></div>
                    <div class="dash-row"><span class="flex-1 muted">Expenses</span><b class="num text-error">−<?= currency($cf['total_expenses']) ?></b></div>
                </div>
                <div class="cq-expanded">
                    <div class="cq-stats-grid">
                        <div class="stat"><div class="stat-label">Tickets</div><div class="stat-value">+<?= currency($cf['ticket_income']) ?></div></div>
                        <div class="stat"><div class="stat-label">Buildings &amp; food</div><div class="stat-value">+<?= currency($cf['building_income']) ?></div></div>
                        <div class="stat"><div class="stat-label">Payroll</div><div class="stat-value">−<?= currency($cf['salaries']) ?></div></div>
                        <div class="stat"><div class="stat-label">Equipment &amp; marketing</div><div class="stat-value">−<?= currency($cf['equipment_cost'] + $cf['marketing_cost']) ?></div></div>
                        <div class="stat cq-large-only"><div class="stat-label">All-time income</div><div class="stat-value"><?= currency($finance['total_income'] ?? 0) ?></div></div>
                        <div class="stat cq-large-only"><div class="stat-label">All-time costs</div><div class="stat-value"><?= currency($finance['total_expenses'] ?? 0) ?></div></div>
                    </div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'overview') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Mountain</h2>
                    <div class="widget-links">
                        <a href="/grooming" class="widget-link">Grooming</a>
                        <a href="/map" class="widget-link">Trail map →</a>
                    </div>
                </div>
                <div class="cq-overview-grid">
                    <a href="/map" class="stat"><div class="stat-label">Slopes open</div><div class="stat-value"><?= $slopeCount ?> <small>/ <?= $totalSlopes ?? $slopeCount ?></small></div></a>
                    <a href="/map" class="stat"><div class="stat-label">Lifts open</div><div class="stat-value"><?= $liftCount ?> <small>/ <?= $totalLifts ?? $liftCount ?></small></div></a>
                    <a href="/staff" class="stat"><div class="stat-label">Staff</div><div class="stat-value"><?= $staffCount ?></div></a>
                    <a href="/buildings" class="stat"><div class="stat-label">Buildings</div><div class="stat-value"><?= $buildingCount ?></div></a>
                    <a href="/grooming" class="stat cq-large-only">
                        <div class="stat-label">Trail condition</div>
                        <div class="stat-value <?= ($avgSlopeCondition ?? 100) >= 70 ? '' : 'text-warning' ?>"><?= $avgSlopeCondition ?? 100 ?>%</div>
                        <div class="stat-note"><?= ($ungroomedCount ?? 0) > 0 ? ($ungroomedCount . ' need grooming') : 'All groomed' ?></div>
                    </a>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'season') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Season <?= $seasonNum ?> <span class="aside">day <?= $seasonProgress ?> of <?= $seasonLength ?></span></h2>
                </div>
                <div class="cq-compact">
                    <div class="widget-center">
                        <div class="radial-progress text-primary text-sm" style="--value:<?= $seasonPct ?>;--size:4rem;--thickness:4px;" role="progressbar" aria-valuenow="<?= $seasonPct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Season progress"><?= $seasonPct ?>%</div>
                    </div>
                </div>
                <div class="cq-expanded">
                    <div class="cq-season-grid">
                        <div>
                            <div class="flex justify-between text-xs mb-1.5"><span>Winter</span><span class="muted num"><?= min($seasonProgress, 100) ?> / 100 days</span></div>
                            <progress class="progress progress-primary w-full" value="<?= min($seasonProgress, 100) ?>" max="100"></progress>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1.5"><span>Summer</span><span class="muted num"><?= max(0, $seasonProgress - 100) ?> / 35 days</span></div>
                            <progress class="progress w-full" value="<?= max(0, $seasonProgress - 100) ?>" max="35"></progress>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'weather_mini' && $weather) : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Weather</h2>
                    <a href="/weather" class="widget-link">Forecast →</a>
                </div>
                <div class="cq-compact">
                    <div class="widget-center">
                        <i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?> text-2xl" aria-hidden="true"></i>
                        <div class="text-xl font-semibold mt-1.5"><?= $weather['temp_formatted'] ?></div>
                        <div class="text-xs muted"><?= esc($weather['condition_name']) ?></div>
                    </div>
                </div>
                <div class="cq-expanded my-auto">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?> text-2xl" aria-hidden="true"></i>
                            <div>
                                <div class="text-xl font-semibold"><?= $weather['temp_formatted'] ?> <span class="text-sm font-normal muted"><?= esc($weather['condition_name']) ?></span></div>
                                <div class="text-xs muted mt-0.5">Wind <?= speed((int)$weather['wind']) ?> · new snow <?= snow($weather['snowfall'] ?? 0) ?> · base <?= snow($weather['snow_base'] ?? 0) ?></div>
                            </div>
                        </div>
                        <?php if (!empty($forecast)) : ?>
                        <div class="forecast">
                            <?php foreach (array_slice($forecast, 0, 5) as $i => $fc) : ?>
                            <div class="<?= $i >= 3 ? 'cq-large-only' : '' ?>">
                                <div class="text-[11px] muted"><?= $i === 0 ? 'Tmrw' : '+' . ($i + 1) . 'd' ?></div>
                                <i class="fa-solid <?= $wIcons[$fc['condition']] ?? 'fa-cloud' ?> text-xs my-1" aria-hidden="true"></i>
                                <div class="text-xs num"><?= temp($fc['temp']) ?></div>
                            </div>
                            <?php endforeach ?>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'actions') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Manage</h2>
                </div>
                <nav class="cq-actions-grid" aria-label="Manage resort">
                    <a href="/map" class="dash-action-tile"><i class="fa-solid fa-map" aria-hidden="true"></i>Trail map</a>
                    <a href="/resort" class="dash-action-tile"><i class="fa-solid fa-sliders" aria-hidden="true"></i>Resort</a>
                    <a href="/finances" class="dash-action-tile"><i class="fa-solid fa-coins" aria-hidden="true"></i>Finances</a>
                    <a href="/staff" class="dash-action-tile"><i class="fa-solid fa-users" aria-hidden="true"></i>Staff <span class="muted num"><?= $staffCount ?></span></a>
                    <a href="/grooming" class="dash-action-tile"><i class="fa-solid fa-tractor" aria-hidden="true"></i>Grooming</a>
                    <a href="/snowmaking" class="dash-action-tile"><i class="fa-solid fa-snowflake" aria-hidden="true"></i>Snowmaking</a>
                    <a href="/buildings" class="dash-action-tile"><i class="fa-solid fa-hotel" aria-hidden="true"></i>Buildings</a>
                    <a href="/terrain-parks" class="dash-action-tile"><i class="fa-solid fa-person-snowboarding" aria-hidden="true"></i>Terrain parks</a>
                    <a href="/parking" class="dash-action-tile"><i class="fa-solid fa-square-parking" aria-hidden="true"></i>Parking</a>
                    <a href="/equipment" class="dash-action-tile"><i class="fa-solid fa-toolbox" aria-hidden="true"></i>Equipment</a>
                    <a href="/marketing" class="dash-action-tile"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i>Marketing</a>
                    <a href="/vote" class="dash-action-tile"><i class="fa-solid fa-check-to-slot" aria-hidden="true"></i>Vote</a>
                </nav>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'achievements_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Rewards</h2>
                    <a href="/achievements" class="widget-link">All →</a>
                </div>
                <?php if ($unclaimedAchievements > 0) : ?>
                    <div class="flex items-center justify-between gap-3 my-auto">
                        <div>
                            <div class="text-sm font-semibold"><?= $unclaimedAchievements ?> reward<?= $unclaimedAchievements > 1 ? 's' : '' ?> ready</div>
                            <div class="text-xs muted"><?= $completedAchievements ?? 0 ?> achievements unlocked so far</div>
                        </div>
                        <a href="/achievements" class="btn btn-warning btn-sm">Claim</a>
                    </div>
                <?php else : ?>
                    <div class="cq-ach-mini-grid my-auto">
                        <div class="stat"><div class="stat-label">Completed</div><div class="stat-value"><?= $completedAchievements ?? 0 ?></div></div>
                        <div class="stat"><div class="stat-label">In progress</div><div class="stat-value"><?= count($inProgressAchievements) ?></div></div>
                        <div class="stat"><div class="stat-label">Unclaimed</div><div class="stat-value">0</div></div>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'achievements' || $w['widget_key'] === 'getting_started') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Achievements in progress</h2>
                    <a href="/achievements" class="widget-link">All →</a>
                </div>
                <?php if (empty($inProgressAchievements)) : ?>
                    <div class="widget-center text-sm muted">Nothing left to unlock.</div>
                <?php else : ?>
                    <div class="flex-1 overflow-y-auto">
                        <?php foreach (array_slice($inProgressAchievements, 0, 4) as $ach) : ?>
                        <?php $pct = $ach['target'] > 0 ? min(100, (int) round($ach['progress'] / $ach['target'] * 100)) : 0; ?>
                        <div class="dash-row" style="display:block">
                            <div class="flex justify-between items-baseline gap-3 mb-1.5">
                                <span class="truncate"><?= esc($ach['name']) ?></span>
                                <span class="text-xs muted num whitespace-nowrap"><?= number_format($ach['progress']) ?> / <?= number_format($ach['target']) ?></span>
                            </div>
                            <progress class="progress progress-primary w-full h-1.5" value="<?= $ach['progress'] ?>" max="<?= max(1, $ach['target']) ?>" aria-label="<?= $pct ?>%"></progress>
                        </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'activity') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Recent activity</h2>
                    <a href="/activity" class="widget-link">Full log →</a>
                </div>
                <?php if (empty($recentActivity)) : ?>
                    <div class="widget-center text-sm muted">Nothing yet.</div>
                <?php else : ?>
                    <div id="activityFeedList" class="flex-1 overflow-y-auto">
                        <?php foreach (array_slice($recentActivity, 0, 6) as $log) : ?>
                        <div class="dash-row">
                            <span class="flex-1 truncate"><?= esc($log['message']) ?></span>
                            <span class="day">Day <?= esc($log['game_day'] ?? $gameDay) ?></span>
                        </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'parking_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Parking</h2>
                    <a href="/parking" class="widget-link">Manage</a>
                </div>
                <div class="grid grid-cols-3 gap-3 my-auto">
                    <div class="stat"><div class="stat-label">Lots</div><div class="stat-value"><?= count($parkingFacilities) ?></div></div>
                    <div class="stat"><div class="stat-label">Spaces</div><div class="stat-value"><?= number_format($totalParkingCap) ?></div></div>
                    <div class="stat"><div class="stat-label">Full</div><div class="stat-value <?= $parkingPct > 90 ? 'text-error' : ($parkingPct > 70 ? 'text-warning' : '') ?>"><?= $parkingPct ?>%</div></div>
                </div>
                <progress class="progress w-full h-1.5 mt-3" value="<?= $totalOccupied ?>" max="<?= max(1, $totalParkingCap) ?>"></progress>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'terrain_parks_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Terrain parks</h2>
                    <a href="/terrain-parks" class="widget-link">Manage</a>
                </div>
                <div class="grid grid-cols-3 gap-3 my-auto">
                    <div class="stat"><div class="stat-label">Open</div><div class="stat-value"><?= count($openParks) ?></div></div>
                    <div class="stat"><div class="stat-label">Building</div><div class="stat-value"><?= count($buildingParks) ?></div></div>
                    <div class="stat"><div class="stat-label">Riders</div><div class="stat-value"><?= number_format($totalParkVisitors) ?></div></div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'finances_mini' && $finance) : ?>
                <div class="widget-header">
                    <h2 class="widget-title">All-time finances</h2>
                    <a href="/finances" class="widget-link">Details</a>
                </div>
                <div class="my-auto">
                    <div class="dash-row"><span class="flex-1 muted">Income</span><span class="num"><?= currency($finance['total_income'] ?? 0) ?></span></div>
                    <div class="dash-row"><span class="flex-1 muted">Expenses</span><span class="num"><?= currency($finance['total_expenses'] ?? 0) ?></span></div>
                    <div class="dash-row"><span class="flex-1 muted">Net</span><b class="num <?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></b></div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'staff_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Staff</h2>
                    <a href="/staff" class="widget-link">Manage</a>
                </div>
                <?php if (empty($staffAll)) : ?>
                    <div class="widget-center text-sm muted">
                        <p>No one hired yet.</p>
                        <a href="/staff/hire" class="btn btn-xs btn-primary mt-2">Hire staff</a>
                    </div>
                <?php else : ?>
                    <div class="mb-3">
                        <?php foreach ($roles as $role => $count) : ?>
                        <div class="dash-row"><span class="flex-1 capitalize truncate"><?= esc(str_replace('_', ' ', $role)) ?></span><span class="num"><?= $count ?></span></div>
                        <?php endforeach ?>
                    </div>
                    <div class="flex items-center gap-2 mt-auto text-xs">
                        <span class="muted">Morale</span>
                        <progress class="progress <?= $avgMorale >= 60 ? 'progress-success' : ($avgMorale >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-1.5" value="<?= $avgMorale ?>" max="100"></progress>
                        <span class="num"><?= $avgMorale ?>%</span>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'equipment_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Equipment</h2>
                    <a href="/equipment" class="widget-link">Fleet</a>
                </div>
                <div class="stat my-auto"><div class="stat-label">Units</div><div class="stat-value"><?= count($equipment) ?></div></div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'insurance_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Insurance</h2>
                    <a href="/insurance" class="widget-link">Policies</a>
                </div>
                <div class="grid grid-cols-2 gap-3 my-auto">
                    <div class="stat"><div class="stat-label">Active</div><div class="stat-value"><?= count($activeIns) ?></div></div>
                    <div class="stat"><div class="stat-label">Available</div><div class="stat-value"><?= max(0, count($insurance) - count($activeIns)) ?></div></div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'loans_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Loans</h2>
                    <a href="/bank" class="widget-link">Bank</a>
                </div>
                <?php if (empty($loans)) : ?>
                    <div class="stat my-auto"><div class="stat-label">Owed</div><div class="stat-value">None</div></div>
                <?php else : ?>
                    <div class="stat my-auto">
                        <div class="stat-label"><?= count($loans) ?> loan<?= count($loans) > 1 ? 's' : '' ?> outstanding</div>
                        <div class="stat-value"><?= currency(array_sum(array_column($loans, 'remaining'))) ?></div>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'marketing_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Marketing</h2>
                    <a href="/marketing" class="widget-link">Campaigns</a>
                </div>
                <div class="stat my-auto"><div class="stat-label">Running campaigns</div><div class="stat-value"><?= count($marketing) ?></div></div>
            <?php endif ?>

            </div><!-- /widget-content -->
        </div><!-- /widget-item -->
    <?php endforeach ?>
    </div>

    <div id="hiddenWidgets" class="hidden mt-6 p-4 rounded-lg border border-base-300" inert>
        <h2 class="text-sm font-semibold mb-3">Hidden widgets</h2>
        <div class="flex flex-wrap gap-2" id="hiddenWidgetList">
        <?php foreach ($widgets as $w) : ?>
            <?php if ($w['visible']) continue; $info = $availableWidgets[$w['widget_key']] ?? null; if (!$info) continue; ?>
            <button type="button" onclick="showWidget('<?= $w['widget_key'] ?>')" class="btn btn-outline btn-sm gap-1.5 hidden-widget-btn" data-key="<?= $w['widget_key'] ?>">
                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> <?= esc($info['name']) ?>
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
    hiddenSection.toggleAttribute('inert', !editMode);
    editBtn.setAttribute('aria-pressed', editMode ? 'true' : 'false');
    editBtn.innerHTML = editMode
        ? 'Done'
        : 'Customize';
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
    const widgetName = (el && el.dataset.name) ? el.dataset.name : key.replace(/_/g, ' ');
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
    btn.innerHTML = '<i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> ' + esc2(widgetName);
    btn.onclick = () => showWidget(key);
    list.appendChild(btn);
}

function esc2(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

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
            const active = b.dataset.sz === size;
            b.classList.toggle('btn-primary', active);
            b.classList.toggle('btn-ghost', !active);
            b.setAttribute('aria-pressed', active ? 'true' : 'false');
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
        const refNode = items.indexOf(dragEl) < items.indexOf(t) ? t.nextSibling : t;
        if ('moveBefore' in Element.prototype) {
            container.moveBefore(dragEl, refNode);
        } else {
            container.insertBefore(dragEl, refNode);
        }
    }
});

if (sessionStorage.getItem('editMode')) {
    sessionStorage.removeItem('editMode');
    toggleEditMode();
}
</script>
<?php if (featureEnabled("live_visitors")) : ?>
<script data-cfasync="false">
(function(){var el=document.getElementById("liveVisitors");if(!el)return;var base=parseInt(el.dataset.count)||0,lifts=parseInt(el.dataset.lifts)||0,slopes=parseInt(el.dataset.slopes)||0;var capacity=Math.max(1,lifts*80+slopes*40);var cur=Math.min(base,capacity);el.textContent=cur.toLocaleString();var nv=document.getElementById("navVisitors");if(nv)nv.textContent=cur.toLocaleString()+" visitors";function tick(){if(lifts===0&&slopes===0){el.textContent="0";setTimeout(tick,10000);return}var range=Math.max(1,Math.ceil(capacity*0.02));var d=Math.floor(Math.random()*(range*2+1))-range;if(Math.random()<0.4)d=0;cur=Math.max(0,Math.min(cur+d,capacity));el.textContent=cur.toLocaleString();var nv2=document.getElementById("navVisitors");if(nv2)nv2.textContent=cur.toLocaleString()+" visitors";if(d!==0){/* no colour flash */}setTimeout(tick,8000+Math.floor(Math.random()*7000))}setTimeout(tick,5000)})();
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
        fetch("/dashboard/activity", { priority: "low", headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then(function(r) { return r.json(); })
        .then(function(logs) {
            if (!Array.isArray(logs) || !logs.length) return;
            feedList.innerHTML = "";
            logs.slice(0, 6).forEach(function(log) {
                var div = document.createElement("div");
                div.className = "dash-row";
                div.innerHTML = '<span class="flex-1 truncate">' + esc(log.message) + '</span>' +
                    '<span class="day">Day ' + esc(log.game_day || '') + '</span>';
                feedList.appendChild(div);
            });
        }).catch(function(){});
    }
    setInterval(refresh, 30000);
})();
</script>
<?php endif ?>
<?= $this->endSection() ?>
