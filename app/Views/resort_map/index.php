<?php
$csrfName        = csrf_token();
$csrfHash        = csrf_hash();
$segmentsJson    = json_encode($segments ?? []);
$sectorsJson     = json_encode($sectors ?? []);
$builtIdsJson    = json_encode($builtSegmentIds ?? []);
$builtItemsJson  = json_encode($builtItems ?? new \stdClass());
$releasedIdsJson = json_encode($releasedSectorIds ?? []);
$resortMapsJson  = json_encode($resortMaps ?? []);
$userCashVal     = (int) ($userCash ?? 0);
$distUnit        = function_exists('distanceUnit') ? distanceUnit() : 'm';
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="/css/leaflet.css" />
<link rel="preload" href="<?= esc(str_replace('.jpg', '_med.jpg', $mapConfig['image'])) ?>" as="image" fetchpriority="high">

<style>
#map {
    height: 100%;
    width: 100%;
    background: #0f172a;
    position: relative;
    z-index: 0;
    user-select: none;
}
.map-legend {
    position: absolute;
    bottom: 14px;
    left: 14px;
    z-index: 800;
    background: rgba(15, 23, 42, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 11px;
    color: #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 5px;
    backdrop-filter: blur(8px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
}
.map-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
}
.map-legend-item span.legend-swatch {
    width: 22px;
    height: 4px;
    border-radius: 2px;
    display: inline-block;
}
.build-fab {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 900;
}
/* Ensure tutorial widget does not cover the build button on the map page */
#tutorialWidget {
    bottom: 5.5rem !important;
}
.leaflet-interactive {
    cursor: pointer !important;
}
#map.drawing-cursor,
#map.drawing-cursor .leaflet-interactive {
    cursor: crosshair !important;
}

/* Admin Floating Top Editor Toolbar - centered via flexbox wrapper & margin auto (immune to transform overrides) */
.admin-editor-toolbar-wrap {
    position: absolute;
    top: 12px;
    left: 0;
    right: 0;
    z-index: 850;
    display: flex;
    justify-content: center;
    pointer-events: none;
    padding: 0 12px;
}
@media (min-width: 900px) {
    .admin-editor-toolbar-wrap.has-open-drawer {
        right: 406px;
    }
}
.admin-editor-toolbar {
    pointer-events: auto;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    background: rgba(15, 23, 42, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 14px;
    box-shadow: 0 14px 34px -5px rgba(0, 0, 0, 0.7), 0 4px 14px rgba(0, 0, 0, 0.45);
    padding: 7px 12px;
    max-width: 96vw;
    width: fit-content;
}
.admin-editor-toolbar .select,
.admin-editor-toolbar .input,
#resortSelect,
#buildDiffFilter,
#inspectorFilterType {
    width: auto !important;
    display: inline-flex !important;
}

/* Custom Vertex Handle */
.custom-vertex-wrap {
    background: transparent;
    border: none;
}
.custom-vertex-handle {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px solid #f59e0b;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.85);
    cursor: grab !important;
    transition: transform 0.1s ease, box-shadow 0.1s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 800;
    color: #0f172a;
}
.custom-vertex-handle.is-start {
    width: 20px;
    height: 20px;
    background: #22c55e;
    border: 2.5px solid #ffffff;
    color: #ffffff;
}
.custom-vertex-handle.is-end {
    width: 20px;
    height: 20px;
    background: #ef4444;
    border: 2.5px solid #ffffff;
    color: #ffffff;
}
.custom-vertex-handle.is-selected {
    box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.75), 0 2px 8px rgba(0, 0, 0, 0.85);
    transform: scale(1.25);
}
.custom-vertex-handle:hover {
    transform: scale(1.3);
}
.custom-vertex-handle:active {
    cursor: grabbing !important;
}

/* Custom Midpoint Handle */
.custom-midpoint-wrap {
    background: transparent;
    border: none;
}
.custom-midpoint-handle {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.65);
    cursor: copy !important;
    opacity: 0.85;
    transition: all 0.12s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 8px;
    font-weight: 900;
}
.custom-midpoint-handle:hover {
    transform: scale(1.45);
    opacity: 1;
}

/* Snap Indicator Ring */
.snap-indicator-wrap {
    background: transparent;
    border: none;
    pointer-events: none;
}
.snap-indicator-ring {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2.5px solid #38bdf8;
    background: rgba(56, 189, 248, 0.22);
    box-shadow: 0 0 12px rgba(56, 189, 248, 0.9);
    animation: pulseSnap 1s infinite alternate;
}
@keyframes pulseSnap {
    from { transform: scale(0.9); opacity: 0.8; }
    to { transform: scale(1.15); opacity: 1; }
}

/* Admin Popover */
.admin-leaflet-popup .leaflet-popup-content-wrapper {
    background: #1e293b !important;
    color: #f1f5f9 !important;
    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    border-radius: 12px !important;
    box-shadow: 0 14px 32px rgba(0, 0, 0, 0.65) !important;
    padding: 4px;
}
.admin-leaflet-popup .leaflet-popup-tip {
    background: #1e293b !important;
}

/* Side Drawers - Floating Glassmorphic Panel inside Map Viewport */
.map-side-drawer {
    position: absolute;
    top: 12px;
    right: 12px;
    bottom: 12px;
    width: 390px;
    max-width: calc(100vw - 24px);
    background: rgba(15, 23, 42, 0.96);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 16px;
    z-index: 950;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.75), 0 0 0 1px rgba(255, 255, 255, 0.05);
    overflow: hidden;
    color: #f1f5f9;
}

.drawer-header {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: linear-gradient(180deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.4) 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-shrink: 0;
}

.drawer-header-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(56, 189, 248, 0.15);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #38bdf8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.drawer-cash-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #4ade80;
    background: rgba(34, 197, 94, 0.14);
    border: 1px solid rgba(34, 197, 94, 0.3);
    padding: 1px 8px;
    border-radius: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 700;
    font-size: 11px;
}

.drawer-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
}
.drawer-close-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.45);
    color: #fca5a5;
}

/* Segmented Tabs inside Build Drawer */
.drawer-segmented-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px;
    background: #090d16;
    padding: 4px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 12px;
}
.drawer-seg-tab {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 8px 10px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
}
.drawer-seg-tab:hover {
    color: #e2e8f0;
    background: rgba(255, 255, 255, 0.04);
}
.drawer-seg-tab.is-active {
    background: #2563eb;
    color: #ffffff;
    border-color: rgba(147, 197, 253, 0.35);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}
.drawer-seg-tab .tab-count-pill {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.28);
    color: inherit;
    font-family: ui-monospace, SFMono-Regular, monospace;
}

/* Filter & Search Bar inside Drawer */
.drawer-search-wrap {
    position: relative;
    flex: 1;
}
.drawer-search-wrap i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 12px;
    pointer-events: none;
}
.drawer-search-input {
    width: 100%;
    height: 34px;
    padding: 0 10px 0 30px;
    background: #0b111e;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 9px;
    color: #f1f5f9;
    font-size: 12px;
    outline: none;
}
.drawer-search-input:focus {
    border-color: #38bdf8;
    box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
}
.drawer-select {
    height: 34px;
    padding: 0 26px 0 10px;
    background: #0b111e;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 9px;
    color: #e2e8f0;
    font-size: 11px;
    font-weight: 600;
    outline: none;
    cursor: pointer;
}
.drawer-select:focus {
    border-color: #38bdf8;
}

/* Run & Lift Item Cards in Build List */
.build-item-card {
    display: flex;
    align-items: center;
    gap: 11px;
    width: 100%;
    padding: 10px 12px;
    background: rgba(30, 41, 59, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    text-align: left;
    cursor: pointer;
    margin-bottom: 7px;
}
.build-item-card:hover {
    background: rgba(30, 41, 59, 0.95);
    border-color: rgba(56, 189, 248, 0.45);
}
.build-item-card.is-selected {
    background: rgba(37, 99, 235, 0.2);
    border-color: #38bdf8;
    box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.35), 0 6px 16px rgba(0, 0, 0, 0.4);
}
.build-item-emblem {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-weight: 900;
}
.build-item-info {
    flex: 1;
    min-width: 0;
}
.build-item-title-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 3px;
}
.build-item-name {
    font-size: 13px;
    font-weight: 700;
    color: #f8fafc;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.build-diff-pill {
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 1px 6px;
    border-radius: 5px;
    flex-shrink: 0;
}
.build-item-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #94a3b8;
    font-family: ui-monospace, SFMono-Regular, monospace;
}
.build-item-price-col {
    text-align: right;
    flex-shrink: 0;
}
.build-item-price {
    font-size: 12px;
    font-weight: 800;
    font-family: ui-monospace, SFMono-Regular, monospace;
}
.build-item-cta {
    font-size: 10px;
    color: #38bdf8;
    font-weight: 600;
    margin-top: 1px;
}

/* Selected Segment Configuration Blueprint Card */
.blueprint-card {
    background: linear-gradient(180deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%);
    border: 1.5px solid rgba(56, 189, 248, 0.5);
    border-radius: 14px;
    padding: 14px;
    margin-bottom: 14px;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(56, 189, 248, 0.15);
}
.blueprint-stat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    background: rgba(9, 13, 22, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 10px;
    padding: 8px 10px;
    margin-bottom: 12px;
}
.blueprint-stat-cell {
    display: flex;
    flex-direction: column;
}
.blueprint-stat-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    font-weight: 700;
}
.blueprint-stat-val {
    font-size: 12px;
    font-weight: 700;
    color: #e2e8f0;
    font-family: ui-monospace, SFMono-Regular, monospace;
    margin-top: 1px;
}

/* Radio Option Cards inside Blueprint */
.build-option-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 11px;
    background: rgba(15, 23, 42, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 9px;
    cursor: pointer;
    font-size: 12px;
    color: #cbd5e1;
}
.build-option-card:hover {
    border-color: rgba(255, 255, 255, 0.25);
    background: rgba(30, 41, 59, 0.8);
}
.build-option-card.is-checked {
    background: rgba(56, 189, 248, 0.15);
    border-color: #38bdf8;
    color: #ffffff;
    box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.3);
}
.seat-pill-btn {
    flex: 1;
    text-align: center;
    padding: 7px 6px;
    background: rgba(15, 23, 42, 0.75);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    cursor: pointer;
    font-family: ui-monospace, SFMono-Regular, monospace;
}
.seat-pill-btn:hover {
    border-color: rgba(255, 255, 255, 0.25);
    color: #f1f5f9;
}
.seat-pill-btn.is-checked {
    background: rgba(245, 158, 11, 0.18);
    border-color: #f59e0b;
    color: #fbbf24;
    box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.3);
}
</style>

<!-- Top Header Bar -->
<div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 border-b border-base-300 bg-base-100/95">
    <div class="flex items-center gap-2.5">
        <a href="/resort" class="btn btn-ghost btn-sm btn-circle" title="Back to Resort"><i class="fa-solid fa-arrow-left"></i></a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-base sm:text-lg font-bold leading-tight">Trail Map</h1>
                <span class="badge badge-primary badge-sm font-semibold"><?= esc($mapConfig['name']) ?></span>
            </div>
            <div class="flex items-center gap-3 text-xs text-base-content/60 mt-0.5">
                <span class="flex items-center gap-1"><i class="fa-solid fa-person-skiing text-success"></i> <b id="slopeCountEl"><?= $slopeCount ?></b> Slopes</span>
                <span class="flex items-center gap-1"><i class="fa-solid fa-elevator text-warning"></i> <b id="liftCountEl"><?= $liftCount ?></b> Lifts</span>
                <span class="flex items-center gap-1"><i class="fa-solid fa-route text-info"></i> <b id="segmentCountEl"><?= $segmentCount ?></b> Total</span>
            </div>
        </div>
    </div>

    <!-- Filter Pills & Map Actions -->
    <div class="flex items-center gap-1.5 flex-wrap">
        <div class="join bg-base-200/80 p-0.5 rounded-lg border border-base-300 hidden md:flex">
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-primary" data-filter="all">All</button>
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-ghost" data-filter="lift"><i class="fa-solid fa-elevator text-warning mr-1"></i>Lifts</button>
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-ghost" data-filter="slope"><i class="fa-solid fa-person-skiing text-success mr-1"></i>Slopes</button>
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-ghost" data-filter="built">Built</button>
            <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-ghost" data-filter="unassigned" title="Segments with Sector 0">Unassigned</button>
            <?php endif; ?>
        </div>

        <button type="button" id="btnFitMap" class="btn btn-ghost btn-sm btn-square border border-base-300" title="Reset Map Zoom (F)">
            <i class="fa-solid fa-expand"></i>
        </button>

        <?php if ($isAdmin): ?>
        <button type="button" id="btnOpenInspector" class="btn btn-outline btn-sm gap-1.5" title="Open Segment & Sector Manager">
            <i class="fa-solid fa-list-check text-info"></i> <span class="hidden sm:inline">Segments & Sectors</span>
        </button>
        <?php endif; ?>

        <button type="button" id="headerBuildBtn" class="btn btn-primary btn-sm gap-1.5 shadow-sm">
            <i class="fa-solid fa-hammer"></i> Build Runs
        </button>

        <?php if ($isAdmin): ?>
        <select id="resortSelect" class="select select-sm select-bordered font-medium">
            <?php foreach ($resortMaps as $key => $rm): ?>
            <option value="<?= $key ?>" <?= $key === $resortMap ? 'selected' : '' ?>><?= esc($rm['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
</div>

<!-- Main Map Viewport -->
<div class="relative w-full overflow-hidden" style="height:calc(100vh - 122px)">
    <div id="map" data-image="<?= esc($mapConfig['image']) ?>"></div>

    <!-- Initial Loader -->
    <div id="mapLoader" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:100;text-align:center;pointer-events:none">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <p class="text-sm text-base-content/60 mt-2">Loading trail map...</p>
    </div>

    <!-- Subtle HD Resolution Indicator -->
    <div id="hdBadge" class="badge badge-neutral badge-sm gap-1.5 py-2.5 px-3 shadow-lg" style="display:none;position:absolute;bottom:14px;right:84px;z-index:800;background:rgba(15,23,42,0.88);border-color:rgba(255,255,255,0.12)">
        <span class="loading loading-spinner loading-xs text-info"></span>
        <span class="text-[11px]">Loading HD terrain...</span>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Admin Floating Top Editor Toolbar -->
    <div class="admin-editor-toolbar-wrap">
    <div id="adminEditorToolbar" class="admin-editor-toolbar">
        <!-- Idle View -->
        <div id="toolIdleView" class="flex items-center gap-2 flex-wrap">
            <span class="badge badge-primary font-bold text-xs gap-1.5 py-2.5 px-3">
                <i class="fa-solid fa-compass-drafting text-[11px]"></i> Map Studio
            </span>

            <div class="flex items-center gap-1.5 bg-base-300/70 rounded-lg px-2 py-0.5 border border-white/10">
                <span class="text-[11px] font-semibold text-base-content/70">Sector:</span>
                <select id="toolActiveSector" class="select select-xs select-bordered bg-base-200 text-xs py-0 h-6 min-h-0">
                    <option value="0">Default (0)</option>
                    <?php foreach ($sectors as $sec): ?>
                    <option value="<?= (int) $sec['id'] ?>"><?= esc($sec['name']) ?> (#<?= (int) $sec['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="h-4 w-px bg-white/15 mx-0.5 hidden sm:block"></div>

            <button type="button" id="btnNewLift" class="btn btn-xs btn-warning font-bold gap-1.5 shadow-sm" title="Draw New Lift (Shortcut: L)">
                <i class="fa-solid fa-elevator"></i> + New Lift
            </button>
            <button type="button" id="btnNewSlope" class="btn btn-xs btn-success font-bold gap-1.5 shadow-sm" title="Draw New Slope (Shortcut: S)">
                <i class="fa-solid fa-person-skiing"></i> + New Slope
            </button>

            <div class="h-4 w-px bg-white/15 mx-0.5 hidden sm:block"></div>

            <button type="button" id="btnToggleSnap" class="btn btn-xs btn-info btn-outline gap-1 font-semibold" title="Magnetic Endpoint Snapping (Hold Alt to bypass)">
                <i class="fa-solid fa-magnet"></i> Snap: ON
            </button>
            <button type="button" id="btnToggleSectors" class="btn btn-xs btn-ghost border border-white/10 gap-1" title="Show/Hide Sector Boundaries on Map">
                <i class="fa-solid fa-draw-polygon text-warning"></i> Sectors
            </button>

            <span class="text-[11px] text-base-content/50 hidden xl:inline ml-1">
                <i class="fa-solid fa-keyboard mr-1"></i><b>L</b> Lift • <b>S</b> Slope • Click any line to edit
            </span>
        </div>

        <!-- Active Draw / Edit View -->
        <div id="toolActiveView" class="items-center gap-2 flex-wrap" style="display:none">
            <span id="toolModeBadge" class="badge badge-warning text-xs font-bold gap-1.5 py-2.5 px-2.5">
                <i class="fa-solid fa-pen-nib text-[10px]"></i> <span id="toolModeText">New Lift</span>
            </span>

            <!-- Live HUD Metrics -->
            <div class="flex items-center gap-2 bg-base-300/90 px-2.5 py-1 rounded-lg text-xs font-mono border border-primary/25 shadow-inner">
                <span class="flex items-center gap-1 text-primary font-bold" title="Calculated Segment Length">
                    <i class="fa-solid fa-ruler text-[11px]"></i>
                    <span id="toolLiveLength">0</span> <?= esc($distUnit) ?>
                </span>
                <span class="opacity-30">|</span>
                <span class="flex items-center gap-1 text-info" title="Number of Vertices">
                    <i class="fa-solid fa-location-dot text-[11px]"></i>
                    <span id="toolPointCount">0</span> pts
                </span>
            </div>

            <!-- Inline Segment Name -->
            <input type="text" id="toolSegName" class="input input-xs input-bordered w-32 md:w-40 text-xs font-semibold" placeholder="Segment name">

            <!-- Inline Sector -->
            <select id="toolSegSector" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0" title="Assigned Sector">
                <option value="0">Sector: Default (0)</option>
                <?php foreach ($sectors as $sec): ?>
                <option value="<?= (int) $sec['id'] ?>"><?= esc($sec['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Inline Slope Type (shown for slopes) -->
            <select id="toolSlopeType" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0" style="display:none" title="Slope Discipline">
                <option value="downhill">Downhill</option>
                <option value="crosscountry">Cross-Country</option>
                <option value="snowpark">Snow Park</option>
                <option value="luge">Luge</option>
            </select>

            <!-- Inline Difficulty (shown for slopes) -->
            <select id="toolSegDiff" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0" style="display:none" title="Slope Difficulty">
                <option value="green">🟢 Green (Easy)</option>
                <option value="blue">🔵 Blue (Intermediate)</option>
                <option value="black">⚫ Black (Advanced)</option>
                <option value="double_black">🔴 Double Black (Expert)</option>
            </select>

            <div class="h-4 w-px bg-white/15 mx-0.5 hidden sm:block"></div>

            <!-- Path Manipulation Tools -->
            <button type="button" id="toolBtnAppendToggle" class="btn btn-xs btn-info btn-outline gap-1" style="display:none" title="Toggle whether clicking the map appends new points to the end of this path">
                <i class="fa-solid fa-plus"></i> <span id="toolAppendText">Append: OFF</span>
            </button>
            <button type="button" id="toolBtnReverse" class="btn btn-xs btn-ghost border border-white/15 gap-1" title="Reverse Start (S) and End (E) direction">
                <i class="fa-solid fa-right-left"></i> Reverse
            </button>
            <button type="button" id="toolBtnUndo" class="btn btn-xs btn-warning btn-outline gap-1" title="Undo last point (Z)">
                <i class="fa-solid fa-rotate-left"></i> Undo
            </button>

            <div class="h-4 w-px bg-white/15 mx-0.5 hidden sm:block"></div>

            <!-- Save / Save+Next / Cancel -->
            <button type="button" id="toolBtnSave" class="btn btn-xs btn-success font-bold gap-1 shadow-sm" title="Save Segment (Enter)">
                <i class="fa-solid fa-check"></i> Save
            </button>
            <button type="button" id="toolBtnSaveNext" class="btn btn-xs btn-primary font-bold gap-1 shadow-sm" title="Save & Immediately Draw Next (Shift+Enter)">
                <i class="fa-solid fa-forward-step"></i> Save + Next
            </button>
            <button type="button" id="toolBtnCancel" class="btn btn-xs btn-ghost text-error gap-1" title="Cancel (Esc)">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
        </div>

        <!-- Helper Hint Sub-bar (shown while active) -->
        <div id="toolHelperHint" class="text-[11px] text-base-content/60 pt-1.5 mt-1.5 border-t border-white/10 flex items-center justify-between gap-3" style="display:none">
            <span id="toolHintText">
                <i class="fa-solid fa-circle-info text-info mr-1"></i>
                <b>Click map</b> to add points • <b>Drag white/colored dots</b> to move • <b>Click or drag small midpoints</b> to add bends • <b>Right-click or Alt+click</b> a point to delete it
            </span>
            <span id="snapStatusPill" class="badge badge-xs badge-info gap-1 font-mono" style="display:none"></span>
        </div>
    </div>
    </div>
    <?php endif; ?>

    <!-- Toast Notification (for both Admin & Player actions) -->
    <div id="adminToast" class="toast toast-top toast-end z-[1100] pointer-events-none transition-all duration-300 opacity-0 translate-y-[-10px]">
        <div id="adminToastAlert" class="alert alert-success py-2 px-3.5 text-xs shadow-xl font-semibold">
            <span id="adminToastMsg">Success</span>
        </div>
    </div>

    <!-- Map Legend -->
    <div class="map-legend" id="mapLegend">
        <div class="text-[10px] font-bold uppercase tracking-wider text-base-content/50 mb-0.5">Legend</div>
        <div class="map-legend-item"><span class="legend-swatch" style="background:#f59e0b"></span> Lift</div>
        <div class="map-legend-item"><span class="legend-swatch" style="background:#22c55e"></span> Green (Easy)</div>
        <div class="map-legend-item"><span class="legend-swatch" style="background:#3b82f6"></span> Blue (Intermediate)</div>
        <div class="map-legend-item"><span class="legend-swatch" style="background:#1e293b;border:1px solid #94a3b8"></span> Black (Advanced)</div>
        <div class="map-legend-item"><span class="legend-swatch" style="background:#dc2626"></span> Double Black</div>
        <div class="map-legend-item"><span class="legend-swatch" style="background:#a855f7"></span> Terrain Park / Available</div>
    </div>

    <!-- Player Build Drawer (Docked Floating Panel inside Map Viewport) -->
    <div id="buildDrawer" class="map-side-drawer" style="display:none">
        <div class="drawer-header">
            <div class="flex items-center gap-3 min-w-0">
                <div class="drawer-header-icon">
                    <i class="fa-solid fa-hammer"></i>
                </div>
                <div class="min-w-0">
                    <h2 style="font-size:15px;font-weight:800;color:#f8fafc;line-height:1.2">Build Infrastructure</h2>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span style="font-size:11px;color:#94a3b8">Budget:</span>
                        <span class="drawer-cash-pill" id="drawerCashBalance">$<?= number_format($userCashVal) ?></span>
                    </div>
                </div>
            </div>
            <button type="button" id="closeDrawer" class="drawer-close-btn" title="Close Build Menu (Esc)">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="buildDrawerBody" class="p-3.5 flex-1 overflow-y-auto">
            <!-- Segmented Tabs -->
            <div class="drawer-segmented-tabs">
                <button type="button" class="build-tab drawer-seg-tab is-active" data-tab="lift">
                    <i class="fa-solid fa-elevator" style="color:#fbbf24"></i>
                    <span>Build Lifts</span>
                    <span class="tab-count-pill" id="tabCountLift">0</span>
                </button>
                <button type="button" class="build-tab drawer-seg-tab" data-tab="slope">
                    <i class="fa-solid fa-person-skiing" style="color:#4ade80"></i>
                    <span>Build Slopes</span>
                    <span class="tab-count-pill" id="tabCountSlope">0</span>
                </button>
            </div>

            <!-- Selected Segment Build Configuration Blueprint Card -->
            <div id="segmentDetail" style="display:none">
                <div class="blueprint-card">
                    <div class="flex items-start justify-between gap-2 mb-2.5">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#38bdf8">Selected Blueprint</span>
                                <span id="selSegBadge" class="build-diff-pill"></span>
                            </div>
                            <p style="font-size:15px;font-weight:800;color:#ffffff" class="truncate" id="selSegName">-</p>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button type="button" id="btnZoomSelectedSeg" class="drawer-close-btn" style="width:28px;height:28px;font-size:11px" title="Center map on this run">
                                <i class="fa-solid fa-location-crosshairs"></i>
                            </button>
                            <button type="button" id="btnDeselectSeg" class="drawer-close-btn" style="width:28px;height:28px;font-size:11px" title="Deselect segment">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="blueprint-stat-grid">
                        <div class="blueprint-stat-cell">
                            <span class="blueprint-stat-label">Length</span>
                            <span class="blueprint-stat-val" id="selSegLength">-</span>
                        </div>
                        <div class="blueprint-stat-cell">
                            <span class="blueprint-stat-label">Build Time</span>
                            <span class="blueprint-stat-val" id="selSegTime">-</span>
                        </div>
                        <div class="blueprint-stat-cell">
                            <span class="blueprint-stat-label">Sector</span>
                            <span class="blueprint-stat-val truncate" id="selSegSector" style="font-family:inherit">-</span>
                        </div>
                    </div>

                    <!-- Lift Configuration -->
                    <div id="liftOptions">
                        <div class="flex items-center justify-between mb-1.5">
                            <span style="font-size:11px;font-weight:700;color:#cbd5e1;text-transform:uppercase;letter-spacing:0.04em">Lift System</span>
                            <span style="font-size:10px;color:#64748b">Hourly Capacity</span>
                        </div>
                        <div class="flex flex-col gap-1.5 mb-3" id="liftTypeGrid">
                            <label class="build-option-card" data-lift-card="button">
                                <input type="radio" name="liftType" value="button" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#94a3b8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">Button / Surface Lift</div>
                                        <div style="font-size:10px;color:#64748b">$80 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:monospace;font-size:11px;font-weight:700;color:#fbbf24">1,000/hr</span>
                            </label>
                            <label class="build-option-card is-checked" data-lift-card="chair_fixed">
                                <input type="radio" name="liftType" value="chair_fixed" class="hidden" checked>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#38bdf8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">Fixed-Grip Chairlift</div>
                                        <div style="font-size:10px;color:#64748b">$150 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:monospace;font-size:11px;font-weight:700;color:#fbbf24">2,400/hr</span>
                            </label>
                            <label class="build-option-card" data-lift-card="chair_detach">
                                <input type="radio" name="liftType" value="chair_detach" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#94a3b8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">Detachable High-Speed</div>
                                        <div style="font-size:10px;color:#64748b">$250 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:monospace;font-size:11px;font-weight:700;color:#fbbf24">3,400/hr</span>
                            </label>
                            <label class="build-option-card" data-lift-card="gondola">
                                <input type="radio" name="liftType" value="gondola" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#94a3b8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">8-10p Cabin Gondola</div>
                                        <div style="font-size:10px;color:#64748b">$400 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:monospace;font-size:11px;font-weight:700;color:#fbbf24">3,500/hr</span>
                            </label>
                            <label class="build-option-card" data-lift-card="cable_car">
                                <input type="radio" name="liftType" value="cable_car" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#94a3b8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">Aerial Tram / Cable Car</div>
                                        <div style="font-size:10px;color:#64748b">$600 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:monospace;font-size:11px;font-weight:700;color:#fbbf24">4,000/hr</span>
                            </label>
                        </div>

                        <p style="font-size:11px;font-weight:700;color:#cbd5e1;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px">Carrier Size (Seats / Cabin)</p>
                        <div class="flex gap-1.5 mb-3" id="seatGrid"></div>
                    </div>

                    <!-- Slope Configuration -->
                    <div id="slopeOptions">
                        <p style="font-size:11px;font-weight:700;color:#cbd5e1;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px">Trail Discipline</p>
                        <div class="grid grid-cols-2 gap-1.5 mb-3" id="slopeTypeGrid">
                            <label class="build-option-card is-checked" data-slope-card="downhill">
                                <input type="radio" name="slopeType" value="downhill" class="hidden" checked>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-person-skiing text-success"></i>
                                    <span style="font-weight:700">Downhill</span>
                                </div>
                            </label>
                            <label class="build-option-card" data-slope-card="crosscountry">
                                <input type="radio" name="slopeType" value="crosscountry" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-person-skiing-nordic text-info"></i>
                                    <span style="font-weight:700">Cross-Country</span>
                                </div>
                            </label>
                            <label class="build-option-card" data-slope-card="snowpark">
                                <input type="radio" name="slopeType" value="snowpark" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-person-snowboarding" style="color:#c084fc"></i>
                                    <span style="font-weight:700">Snow Park</span>
                                </div>
                            </label>
                            <label class="build-option-card" data-slope-card="luge">
                                <input type="radio" name="slopeType" value="luge" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-sleigh" style="color:#f472b6"></i>
                                    <span style="font-weight:700">Alpine Luge</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3" style="border-top:1px solid rgba(255,255,255,0.1)">
                        <div>
                            <p style="font-size:10px;text-transform:uppercase;letter-spacing:0.05em;color:#94a3b8;font-weight:700">Construction Cost</p>
                            <p style="font-size:18px;font-weight:800;font-family:monospace;line-height:1.2" id="selSegCost">-</p>
                            <p style="font-size:10px;color:#64748b;font-family:monospace" id="selSegAfterCash"></p>
                        </div>
                        <button type="button" id="btnBuild" class="btn btn-success btn-sm gap-1.5 font-bold shadow-lg px-4" style="height:36px">
                            <i class="fa-solid fa-hammer"></i> Construct
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search, Grade Filter & Sort Bar -->
            <div class="flex flex-col gap-2 mb-3">
                <div class="drawer-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="buildSearchInput" class="drawer-search-input" placeholder="Search run or lift by name...">
                </div>
                <div class="flex items-center gap-1.5">
                    <select id="buildDiffFilter" class="drawer-select flex-1" style="display:none">
                        <option value="">All Grades</option>
                        <option value="green">🟢 Green (Easy)</option>
                        <option value="blue">🟦 Blue (Intermediate)</option>
                        <option value="black">◆ Black (Advanced)</option>
                        <option value="double_black">◆◆ Double Black</option>
                    </select>
                    <select id="buildSortSelect" class="drawer-select flex-1">
                        <option value="cost_asc">Sort: Cheapest First</option>
                        <option value="cost_desc">Sort: Expensive First</option>
                        <option value="len_desc">Sort: Longest First</option>
                        <option value="name_asc">Sort: Name (A–Z)</option>
                    </select>
                </div>
            </div>

            <div id="segmentList" class="flex flex-col">
                <p class="text-sm text-base-content/50 text-center py-4">Select a purple line to build</p>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Admin Segment & Sector Inspector Drawer (Docked Floating Panel inside Map Viewport) -->
    <div id="adminInspectorDrawer" class="map-side-drawer" style="display:none">
        <div class="drawer-header">
            <div class="flex items-center gap-3 min-w-0">
                <div class="drawer-header-icon">
                    <i class="fa-solid fa-compass-drafting"></i>
                </div>
                <div class="min-w-0">
                    <h2 style="font-size:15px;font-weight:800;color:#f8fafc;line-height:1.2">Map Inspector</h2>
                    <p style="font-size:11px;color:#94a3b8;margin-top:2px">Manage segments & mountain sectors</p>
                </div>
            </div>
            <button type="button" id="closeInspectorBtn" class="drawer-close-btn" title="Close Inspector">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-3.5 flex-1 overflow-y-auto space-y-5">
            <!-- Sectors Management -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em">Mountain Sectors</p>
                    <div class="flex gap-1">
                        <button type="button" id="autoAssign" class="btn btn-xs btn-outline btn-info gap-1" title="Auto-assign segments inside sector boundaries">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Assign
                        </button>
                        <button type="button" id="newSector" class="btn btn-xs btn-primary gap-1">
                            <i class="fa-solid fa-plus"></i> Add Sector
                        </button>
                    </div>
                </div>

                <!-- Inline New Sector Form (hidden by default) -->
                <div id="newSectorForm" class="p-2.5 rounded-xl bg-base-200 border border-white/10 mb-2 space-y-2" style="display:none">
                    <div class="flex gap-2">
                        <input type="text" id="newSectorName" class="drawer-search-input" style="padding-left:10px" placeholder="Sector name (e.g. Sector 3)">
                        <input type="color" id="newSectorColor" value="#3b82f6" class="w-9 h-8 rounded cursor-pointer bg-transparent border-0">
                    </div>
                    <div class="flex justify-end gap-1.5">
                        <button type="button" id="cancelNewSectorBtn" class="btn btn-xs btn-ghost">Cancel</button>
                        <button type="button" id="saveNewSectorBtn" class="btn btn-xs btn-success">Create</button>
                    </div>
                </div>

                <div id="sectorList" class="flex flex-col gap-1.5"></div>
            </div>

            <!-- Segments Directory -->
            <div style="border-top:1px solid rgba(255,255,255,0.1);padding-top:14px">
                <div class="flex items-center justify-between mb-2.5">
                    <p style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em">All Segments (<span id="inspectorSegCount"><?= count($segments) ?></span>)</p>
                    <div class="flex gap-1">
                        <button type="button" class="btn btn-xs btn-warning gap-1 font-bold" id="inspectorNewLiftBtn"><i class="fa-solid fa-plus"></i> Lift</button>
                        <button type="button" class="btn btn-xs btn-success gap-1 font-bold" id="inspectorNewSlopeBtn"><i class="fa-solid fa-plus"></i> Slope</button>
                    </div>
                </div>

                <div class="flex gap-1.5 mb-2.5">
                    <div class="drawer-search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="inspectorSearch" class="drawer-search-input" placeholder="Filter by name...">
                    </div>
                    <select id="inspectorFilterType" class="drawer-select">
                        <option value="all">All Types</option>
                        <option value="lift">Lifts Only</option>
                        <option value="slope">Slopes Only</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>

                <div id="inspectorSegmentList" class="flex flex-col gap-1.5"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Floating Build FAB -->
<button type="button" class="build-fab btn btn-primary btn-circle btn-lg shadow-xl" id="buildFab" title="Open Build Menu">
    <i class="fa-solid fa-hammer text-xl"></i>
</button>

<script data-cfasync="false" src="/js/leaflet.js"></script>

<script data-cfasync="false">
(function(){
    'use strict';

    var SEGMENTS     = <?= $segmentsJson ?>;
    var SECTORS      = <?= $sectorsJson ?>;
    var BUILT_IDS    = <?= $builtIdsJson ?>;
    var BUILT_ITEMS  = <?= $builtItemsJson ?>;
    var RELEASED_IDS = <?= $releasedIdsJson ?>;
    var IS_ADMIN     = <?= $isAdmin ? 'true' : 'false' ?>;
    var USER_CASH    = <?= $userCashVal ?>;
    var DIST_UNIT    = <?= json_encode($distUnit) ?>;
    var CSRF_NAME    = <?= json_encode($csrfName) ?>;
    var CSRF_HASH    = <?= json_encode($csrfHash) ?>;
    var MAP_IMAGE    = document.getElementById('map').dataset.image;
    var MAP_HEIGHT   = <?= (int) $mapConfig['height'] ?>;
    var MAP_WIDTH    = <?= (int) $mapConfig['width'] ?>;

    var COLORS = {
        lift: '#f59e0b',
        green: '#22c55e',
        blue: '#3b82f6',
        black: '#1e293b',
        double_black: '#dc2626',
        snowpark: '#a855f7',
        terrain_park: '#a855f7',
        crosscountry: '#14b8a6',
        luge: '#ec4899',
        'default': '#94a3b8'
    };
    var COST_PER_METER = {button:80, chair_fixed:150, chair_detach:250, gondola:400, cable_car:600};
    var SEAT_MULT      = {1:0.7, 2:1.0, 3:1.15, 4:1.3, 6:1.6, 8:2.0, 10:2.5, 20:4.0, 30:5.0};
    var SEAT_OPTIONS   = {button:[1,2], chair_fixed:[2,3,4], chair_detach:[4,6,8], gondola:[6,8,10], cable_car:[20,30]};

    var map, mapBounds;
    var segmentLayers = {};
    var hitLayers = {};
    var stationMarkers = [];
    var sectorPolygons = [];
    var showSectorPolygons = false;
    var selectedSegId = null;
    var buildMode = null;
    var activeTab = 'lift';
    var mapFilter = 'all';

    // Admin Editor State
    var editorMode = null; // null | 'draw' | 'edit' | 'sector_boundary'
    var editorType = 'lift'; // 'lift' | 'slope'
    var editingSegId = null;
    var editingSectorId = null;
    var appendInEditMode = false;
    var snapEnabled = true;
    var selectedVertexIdx = -1;
    var editorPoints = [];
    var undoStack = [];
    var editorLine = null;
    var rubberBandLine = null;
    var snapMarker = null;
    var currentSnapTarget = null;
    var vertexMarkers = [];
    var midpointMarkers = [];
    var lastClickTime = 0;

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        var el = document.getElementById('map');
        if (!el || typeof L === 'undefined') {
            console.error('Leaflet not loaded or #map missing');
            return;
        }

        mapBounds = [[0, 0], [MAP_HEIGHT, MAP_WIDTH]];
        map = L.map('map', {
            crs: L.CRS.Simple,
            minZoom: -3,
            maxZoom: 4,
            zoomSnap: 0.25,
            zoomDelta: 0.5,
            zoomControl: true,
            attributionControl: false
        }).setView([MAP_HEIGHT / 2, MAP_WIDTH / 2], 0);

        initProgressiveOverlays();

        map.fitBounds(mapBounds);
        map.setMaxBounds([[-MAP_HEIGHT * 0.2, -MAP_WIDTH * 0.2], [MAP_HEIGHT * 1.2, MAP_WIDTH * 1.2]]);

        var ld = document.getElementById('mapLoader');
        if (ld) ld.remove();

        renderSegments();
        bindUI();
        if (IS_ADMIN) {
            bindAdmin();
            renderInspectorSectors();
            renderInspectorSegments();
        }

        // Auto-open build drawer for new non-admin players with 0 built runs
        if (BUILT_IDS.length === 0 && !IS_ADMIN) {
            setTimeout(function() { openDrawer('slope'); }, 300);
        }
    }

    /**
     * Non-destructive multi-resolution image overlay loader.
     * Never removes the visible base layer before the higher-res image has completely loaded & decoded.
     */
    function initProgressiveOverlays() {
        var basePath = MAP_IMAGE.replace(/\.jpg$/i, '');
        var imgLow   = basePath + '_low.jpg';
        var imgMed   = basePath + '_med.jpg';
        var imgFull  = MAP_IMAGE;

        // 1. Mount low-res base immediately so map is never blank
        var lowOverlay = L.imageOverlay(imgLow, mapBounds, { zIndex: 1 }).addTo(map);
        var medOverlay = null;
        var fullOverlay = null;
        var fullLoading = false;

        // 2. Preload medium resolution right away and layer on top once decoded
        var medPreload = new Image();
        medPreload.onload = function() {
            medOverlay = L.imageOverlay(imgMed, mapBounds, { zIndex: 2 }).addTo(map);
        };
        medPreload.src = imgMed;

        // 3. Load full HD resolution on demand when zooming in (z >= 1.5) without removing medOverlay
        function checkHighRes() {
            if (map.getZoom() >= 1.5 && !fullOverlay && !fullLoading) {
                fullLoading = true;
                var hdBadge = document.getElementById('hdBadge');
                if (hdBadge) hdBadge.style.display = 'inline-flex';

                var fullPreload = new Image();
                fullPreload.onload = function() {
                    fullOverlay = L.imageOverlay(imgFull, mapBounds, { zIndex: 3 }).addTo(map);
                    if (hdBadge) hdBadge.style.display = 'none';
                };
                fullPreload.onerror = function() {
                    if (hdBadge) hdBadge.style.display = 'none';
                };
                fullPreload.src = imgFull;
            }
        }

        map.on('zoomend', checkHighRes);
    }

    function parsePoints(raw) {
        if (!raw) return [];
        var pts = raw;
        if (typeof raw === 'string') {
            try { pts = JSON.parse(raw); } catch (e) { return []; }
        }
        if (!Array.isArray(pts)) return [];
        return pts.map(function(p) {
            if (Array.isArray(p)) return [ Number(p[0]) || 0, Number(p[1]) || 0 ];
            if (p && typeof p === 'object') return [ Number(p.lat || p[0] || 0), Number(p.lng || p[1] || 0) ];
            return [0, 0];
        }).filter(function(p) {
            return !isNaN(p[0]) && !isNaN(p[1]);
        });
    }

    function isSegmentBuilt(segId) {
        return BUILT_IDS.indexOf(String(segId)) !== -1 || BUILT_IDS.indexOf(Number(segId)) !== -1;
    }

    function isSegmentReleased(seg) {
        if (IS_ADMIN) return true;
        if (!RELEASED_IDS || RELEASED_IDS.length === 0) return true;
        var secVal = Number(seg.sector || 0);
        if (!secVal || secVal === 0) return true;
        return RELEASED_IDS.some(function(r) { return Number(r) === secVal; });
    }

    function segColor(s) {
        if (s.type === 'lift') return COLORS.lift;
        var d = s.difficulty || '';
        if (d && COLORS[d]) return COLORS[d];
        if (s.type && COLORS[s.type]) return COLORS[s.type];
        return COLORS['default'];
    }

    function updateHeaderCounts() {
        var lifts = 0, slopes = 0;
        SEGMENTS.forEach(function(s) {
            if (s.type === 'lift') lifts++;
            else slopes++;
        });
        var slopeEl = document.getElementById('slopeCountEl');
        var liftEl  = document.getElementById('liftCountEl');
        var totalEl = document.getElementById('segmentCountEl');
        var inspEl  = document.getElementById('inspectorSegCount');
        if (slopeEl) slopeEl.textContent = slopes;
        if (liftEl)  liftEl.textContent  = lifts;
        if (totalEl) totalEl.textContent = SEGMENTS.length;
        if (inspEl)  inspEl.textContent  = SEGMENTS.length;
    }

    function renderSegments(mode) {
        if (typeof mode !== 'undefined') {
            buildMode = mode;
        }
        Object.values(segmentLayers).forEach(function(l) { map.removeLayer(l); });
        Object.values(hitLayers).forEach(function(l) { map.removeLayer(l); });
        stationMarkers.forEach(function(m) { map.removeLayer(m); });
        segmentLayers = {};
        hitLayers = {};
        stationMarkers = [];

        SEGMENTS.forEach(function(seg) {
            if (editingSegId && Number(seg.id) === Number(editingSegId)) return;

            var ll = parsePoints(seg.points);
            if (ll.length < 2) return;

            var built = isSegmentBuilt(seg.id);

            // Filter by top filter bar
            if (mapFilter === 'lift' && seg.type !== 'lift') return;
            if (mapFilter === 'slope' && seg.type === 'lift') return;
            if (mapFilter === 'built' && !built) return;
            if (mapFilter === 'unassigned' && Number(seg.sector || 0) !== 0) return;

            // Player vs Build Mode visibility rules
            if (!buildMode && !built && !IS_ADMIN) return;
            if (buildMode && !built) {
                if (buildMode === 'lift' && seg.type !== 'lift') return;
                if (buildMode === 'slope' && seg.type === 'lift') return;
                if (!isSegmentReleased(seg)) return;
            }

            var isAvailableHighlight = (buildMode && !built);
            var c = isAvailableHighlight ? '#a855f7' : segColor(seg);
            var weight = built ? 5 : (IS_ADMIN ? 4.5 : 6);
            var opacity = built ? 0.95 : 0.85;
            var dashArray = built ? (seg.type === 'lift' ? '10, 5' : null) : '6, 5';

            // Visible polyline
            var line = L.polyline(ll, {
                color: c,
                weight: weight,
                opacity: opacity,
                dashArray: dashArray,
                lineCap: 'round',
                lineJoin: 'round',
                interactive: false
            }).addTo(map);

            // Wide invisible hit-target polyline for effortless clicking & hovering
            var hitLine = L.polyline(ll, {
                color: '#000',
                weight: 18,
                opacity: 0.001,
                interactive: true
            }).addTo(map);

            var secName = getSectorName(seg.sector);
            var diffLabel = seg.difficulty ? (' • ' + seg.difficulty.replace('_', ' ').toUpperCase()) : '';
            var tooltipHtml = '<div style="text-align:center;padding:2px 6px">' +
                '<b>' + escHtml(seg.name || seg.type) + '</b>' +
                '<br><span style="font-family:monospace">' + Math.round(seg.length_meters || 0) + ' ' + DIST_UNIT + '</span>' + diffLabel +
                (IS_ADMIN ? '<br><span style="opacity:.75;font-size:10px">' + escHtml(secName) + ' • Click to edit</span>' :
                    (!built ? '<br><span style="color:#c084fc;font-weight:700">Click to construct</span>' : '<br><span style="color:#4ade80;font-size:10px">✓ Operational</span>')) +
                '</div>';

            hitLine.bindTooltip(tooltipHtml, { sticky: true });

            hitLine.on('mouseover', function() {
                if (editorMode) return;
                if (!selectedSegId || Number(selectedSegId) !== Number(seg.id)) {
                    line.setStyle({ weight: weight + 3, opacity: 1 });
                }
            });

            hitLine.on('mouseout', function() {
                if (editorMode) return;
                if (!selectedSegId || Number(selectedSegId) !== Number(seg.id)) {
                    line.setStyle({ weight: weight, opacity: opacity });
                }
            });

            hitLine.on('click', function(e) {
                if (L.DomEvent) L.DomEvent.stopPropagation(e);
                if (editorMode) {
                    // If drawing or appending and snap is enabled, allow clicking an existing line to snap a point onto it!
                    if (editorMode === 'draw' || (editorMode === 'edit' && appendInEditMode)) {
                        var pt = getSnappedLatLng(e.latlng);
                        pushUndoState();
                        editorPoints.push([pt.lat, pt.lng]);
                        updateEditorDisplay(true);
                    }
                    return;
                }
                if (IS_ADMIN) {
                    openAdminSegmentPopup(seg, e.latlng);
                } else if (!built) {
                    selectSegment(seg);
                } else {
                    openBuiltSegmentPopup(seg, e.latlng);
                }
            });

            segmentLayers[seg.id] = line;
            hitLayers[seg.id] = hitLine;
        });

        renderSectorPolygons();
    }

    function openBuiltSegmentPopup(seg, latlng) {
        var item = BUILT_ITEMS[seg.id] || {};
        var capInfo = seg.type === 'lift' && item.capacity ? ('<div class="flex justify-between"><span>Capacity:</span><b>' + Number(item.capacity).toLocaleString() + '/hr</b></div>') : '';
        var snowInfo = seg.type !== 'lift' && item.snow_quality ? ('<div class="flex justify-between"><span>Condition:</span><b class="capitalize">' + escHtml(item.snow_quality) + '</b></div>') : '';
        var html = '<div class="p-1 text-xs min-w-[160px]">' +
            '<div class="font-bold text-sm mb-1 flex items-center gap-1.5">' +
                (seg.type === 'lift' ? '<i class="fa-solid fa-elevator text-warning"></i>' : '<i class="fa-solid fa-person-skiing text-success"></i>') +
                ' <span>' + escHtml(seg.name || seg.type) + '</span>' +
            '</div>' +
            '<div class="space-y-1 text-base-content/80">' +
                '<div class="flex justify-between"><span>Length:</span><b class="font-mono">' + Math.round(seg.length_meters || 0) + ' ' + DIST_UNIT + '</b></div>' +
                capInfo + snowInfo +
                '<div class="flex justify-between"><span>Status:</span><span class="badge badge-success badge-xs">Active</span></div>' +
            '</div>' +
        '</div>';
        L.popup({ className: 'admin-leaflet-popup' })
            .setLatLng(latlng)
            .setContent(html)
            .openOn(map);
    }

    function renderSectorPolygons() {
        sectorPolygons.forEach(function(p) { map.removeLayer(p); });
        sectorPolygons = [];
        if (!showSectorPolygons) return;

        SECTORS.forEach(function(sec) {
            if (editingSectorId && Number(sec.id) === Number(editingSectorId)) return;
            var pts = parsePoints(sec.boundary_points);
            if (pts.length < 3) return;
            var col = sec.color || '#3b82f6';
            var poly = L.polygon(pts, {
                color: col,
                weight: 2,
                dashArray: '6, 4',
                fillColor: col,
                fillOpacity: 0.14,
                interactive: false
            }).addTo(map);
            poly.bindTooltip('<b>' + escHtml(sec.name) + '</b>', { permanent: true, direction: 'center', className: 'badge badge-neutral badge-xs opacity-85' });
            sectorPolygons.push(poly);
        });
    }

    function syncDrawerLayoutState() {
        var buildEl = document.getElementById('buildDrawer');
        var inspEl  = document.getElementById('adminInspectorDrawer');
        var isOpen  = (buildEl && buildEl.style.display !== 'none') || (inspEl && inspEl.style.display !== 'none');
        var wrap    = document.querySelector('.admin-editor-toolbar-wrap');
        if (wrap) {
            wrap.classList.toggle('has-open-drawer', !!isOpen);
        }
    }

    function updateTabCounts() {
        var availLifts = 0, availSlopes = 0;
        SEGMENTS.forEach(function(s) {
            if (!isSegmentReleased(s) || isSegmentBuilt(s.id)) return;
            if (s.type === 'lift') availLifts++;
            else availSlopes++;
        });
        var lc = document.getElementById('tabCountLift');
        var sc = document.getElementById('tabCountSlope');
        if (lc) lc.textContent = availLifts;
        if (sc) sc.textContent = availSlopes;
    }

    function getSegmentBaseCost(seg) {
        var m = parseFloat(seg.length_meters) || 0;
        if (seg.type === 'lift') {
            return Math.round(m * 150); // Standard 2-seat fixed chairlift baseline
        }
        return Math.round(m * 50);
    }

    function buildEmblemHtml(seg) {
        if (seg.type === 'lift') {
            return '<div class="build-item-emblem" style="background:rgba(245,158,11,0.16);border:1px solid rgba(245,158,11,0.4);color:#fbbf24;font-size:14px"><i class="fa-solid fa-elevator"></i></div>';
        }
        var d = seg.difficulty || 'blue';
        if (d === 'green') {
            return '<div class="build-item-emblem" style="background:rgba(34,197,94,0.16);border:1px solid rgba(34,197,94,0.4);color:#4ade80;font-size:16px" title="Green Circle (Easy)">●</div>';
        }
        if (d === 'black') {
            return '<div class="build-item-emblem" style="background:#090d16;border:1.5px solid #94a3b8;color:#f8fafc;font-size:16px" title="Black Diamond (Advanced)">◆</div>';
        }
        if (d === 'double_black') {
            return '<div class="build-item-emblem" style="background:rgba(220,38,38,0.18);border:1px solid rgba(248,113,113,0.5);color:#f87171;font-size:13px;letter-spacing:-2px" title="Double Black Diamond (Expert)">◆◆</div>';
        }
        return '<div class="build-item-emblem" style="background:rgba(59,130,246,0.16);border:1px solid rgba(59,130,246,0.45);color:#60a5fa;font-size:14px" title="Blue Square (Intermediate)">■</div>';
    }

    function buildDiffPillHtml(seg) {
        if (seg.type === 'lift') {
            return '<span class="build-diff-pill" style="background:rgba(245,158,11,0.2);color:#fbbf24;border:1px solid rgba(245,158,11,0.35)">LIFT</span>';
        }
        var d = seg.difficulty || 'blue';
        if (d === 'green') {
            return '<span class="build-diff-pill" style="background:rgba(34,197,94,0.2);color:#4ade80;border:1px solid rgba(34,197,94,0.35)">GREEN</span>';
        }
        if (d === 'black') {
            return '<span class="build-diff-pill" style="background:#090d16;color:#f1f5f9;border:1px solid #94a3b8">BLACK</span>';
        }
        if (d === 'double_black') {
            return '<span class="build-diff-pill" style="background:rgba(220,38,38,0.22);color:#fca5a5;border:1px solid rgba(248,113,113,0.45)">DBL BLACK</span>';
        }
        return '<span class="build-diff-pill" style="background:rgba(59,130,246,0.22);color:#93c5fd;border:1px solid rgba(59,130,246,0.4)">BLUE</span>';
    }

    function openDrawer(tab) {
        var insp = document.getElementById('adminInspectorDrawer');
        if (insp) insp.style.display = 'none';

        var drawer = document.getElementById('buildDrawer'), fab = document.getElementById('buildFab');
        drawer.style.display = 'flex';
        if (fab) fab.style.display = 'none';
        var targetTab = tab || 'lift';
        activeTab = targetTab;

        var diffFilter = document.getElementById('buildDiffFilter');
        if (diffFilter) diffFilter.style.display = (targetTab === 'slope') ? 'inline-block' : 'none';

        document.querySelectorAll('.build-tab').forEach(function(b) {
            b.classList.toggle('is-active', b.dataset.tab === targetTab);
        });
        updateTabCounts();
        syncDrawerLayoutState();
        renderSegments(targetTab);
        populateList(targetTab);
        deselectSeg();
    }

    function closeDrawer() {
        var drawer = document.getElementById('buildDrawer'), fab = document.getElementById('buildFab');
        drawer.style.display = 'none';
        if (fab) fab.style.display = '';
        deselectSeg();
        syncDrawerLayoutState();
        renderSegments(null);
    }

    function syncOptionCardHighlights() {
        document.querySelectorAll('#liftTypeGrid .build-option-card').forEach(function(card) {
            var inp = card.querySelector('input[name="liftType"]');
            var checked = !!(inp && inp.checked);
            card.classList.toggle('is-checked', checked);
            var icon = card.querySelector('i');
            if (icon) icon.style.color = checked ? '#38bdf8' : '#94a3b8';
        });
        document.querySelectorAll('#slopeTypeGrid .build-option-card').forEach(function(card) {
            var inp = card.querySelector('input[name="slopeType"]');
            var checked = !!(inp && inp.checked);
            card.classList.toggle('is-checked', checked);
        });
    }

    function bindUI() {
        updateTabCounts();
        var fab = document.getElementById('buildFab');
        var headerBtn = document.getElementById('headerBuildBtn');
        if (fab) fab.addEventListener('click', function() { openDrawer('lift'); });
        if (headerBtn) headerBtn.addEventListener('click', function() { openDrawer('lift'); });
        document.getElementById('closeDrawer').addEventListener('click', closeDrawer);

        var btnZoomSel = document.getElementById('btnZoomSelectedSeg');
        if (btnZoomSel) {
            btnZoomSel.addEventListener('click', function() {
                if (selectedSegId && segmentLayers[selectedSegId] && typeof segmentLayers[selectedSegId].getBounds === 'function') {
                    map.fitBounds(segmentLayers[selectedSegId].getBounds(), { maxZoom: 2.2, padding: [70, 70], animate: true });
                }
            });
        }
        var btnDesel = document.getElementById('btnDeselectSeg');
        if (btnDesel) {
            btnDesel.addEventListener('click', function() {
                deselectSeg();
                populateList(activeTab);
            });
        }

        var btnFit = document.getElementById('btnFitMap');
        if (btnFit) {
            btnFit.addEventListener('click', function() {
                map.fitBounds(mapBounds, { animate: true });
            });
        }

        document.querySelectorAll('.map-filter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                mapFilter = btn.dataset.filter || 'all';
                document.querySelectorAll('.map-filter-btn').forEach(function(b) {
                    b.classList.toggle('btn-primary', b === btn);
                    b.classList.toggle('btn-ghost', b !== btn);
                });
                renderSegments(buildMode);
            });
        });

        document.querySelectorAll('.build-tab').forEach(function(t) {
            t.addEventListener('click', function() {
                openDrawer(t.dataset.tab);
            });
        });

        var searchInput = document.getElementById('buildSearchInput');
        var diffFilter  = document.getElementById('buildDiffFilter');
        var sortSelect  = document.getElementById('buildSortSelect');
        if (searchInput) searchInput.addEventListener('input', function() { populateList(activeTab); });
        if (diffFilter)  diffFilter.addEventListener('change', function() { populateList(activeTab); });
        if (sortSelect)  sortSelect.addEventListener('change', function() { populateList(activeTab); });

        document.querySelectorAll('input[name="liftType"]').forEach(function(el) {
            el.addEventListener('change', function() {
                syncOptionCardHighlights();
                updateSeats();
            });
        });
        document.querySelectorAll('input[name="slopeType"]').forEach(function(el) {
            el.addEventListener('change', function() {
                syncOptionCardHighlights();
                updateCost();
            });
        });
        document.getElementById('btnBuild').addEventListener('click', doBuild);
    }

    function populateList(type) {
        activeTab = type;
        updateTabCounts();
        var list = document.getElementById('segmentList');
        if (!list) return;

        var q    = (document.getElementById('buildSearchInput') ? document.getElementById('buildSearchInput').value : '').trim().toLowerCase();
        var df   = (document.getElementById('buildDiffFilter') ? document.getElementById('buildDiffFilter').value : '');
        var sort = (document.getElementById('buildSortSelect') ? document.getElementById('buildSortSelect').value : 'cost_asc');

        var avail = SEGMENTS.filter(function(s) {
            if (type === 'lift' && s.type !== 'lift') return false;
            if (type === 'slope' && s.type === 'lift') return false;
            if (!isSegmentReleased(s)) return false;
            if (isSegmentBuilt(s.id)) return false;
            if (q && (s.name || '').toLowerCase().indexOf(q) === -1) return false;
            if (type === 'slope' && df && s.difficulty !== df) return false;
            return true;
        });

        avail.sort(function(a, b) {
            var lenA = parseFloat(a.length_meters) || 0;
            var lenB = parseFloat(b.length_meters) || 0;
            if (sort === 'cost_asc') return lenA - lenB;
            if (sort === 'cost_desc') return lenB - lenA;
            if (sort === 'len_desc') return lenB - lenA;
            return String(a.name || '').localeCompare(String(b.name || ''), undefined, { numeric: true, sensitivity: 'base' });
        });

        if (!avail.length) {
            list.innerHTML =
                '<div style="text-align:center;padding:32px 16px;background:rgba(15,23,42,0.5);border:1px dashed rgba(255,255,255,0.1);border-radius:12px">' +
                    '<i class="fa-solid ' + (type === 'lift' ? 'fa-elevator' : 'fa-person-skiing') + '" style="font-size:24px;color:#475569;margin-bottom:8px"></i>' +
                    '<p style="font-size:13px;font-weight:700;color:#cbd5e1">No available ' + type + 's match</p>' +
                    '<p style="font-size:11px;color:#64748b;margin-top:2px">Try clearing your search or grade filter</p>' +
                '</div>';
            return;
        }

        list.innerHTML =
            '<div class="flex items-center justify-between mb-2 px-0.5">' +
                '<span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:#94a3b8">Available ' + (type === 'lift' ? 'Lifts' : 'Slopes') + ' (' + avail.length + ')</span>' +
                '<span style="font-size:10px;color:#64748b"><i class="fa-solid fa-hand-pointer mr-1"></i>Click card or purple line</span>' +
            '</div>';

        avail.forEach(function(seg) {
            var btn = document.createElement('button');
            btn.type = 'button';
            var isSel = selectedSegId && Number(selectedSegId) === Number(seg.id);
            btn.className = 'build-item-card' + (isSel ? ' is-selected' : '');

            var baseCost = getSegmentBaseCost(seg);
            var canAfford = USER_CASH >= baseCost;
            var lenVal = Math.round(seg.length_meters || 0).toLocaleString();
            var days = (seg.length_meters || 0) < 200 ? 1 : ((seg.length_meters || 0) < 500 ? 2 : 3);
            var secTag = (seg.sector && Number(seg.sector) > 0) ? ('<span>• ' + escHtml(getSectorName(seg.sector)) + '</span>') : '';

            btn.innerHTML =
                buildEmblemHtml(seg) +
                '<div class="build-item-info">' +
                    '<div class="build-item-title-row">' +
                        '<span class="build-item-name">' + escHtml(seg.name || 'Unnamed') + '</span>' +
                        buildDiffPillHtml(seg) +
                    '</div>' +
                    '<div class="build-item-meta">' +
                        '<span><i class="fa-solid fa-ruler-horizontal" style="font-size:9px;margin-right:3px;opacity:0.7"></i>' + lenVal + ' ' + DIST_UNIT + '</span>' +
                        '<span>• ' + days + 'd</span>' +
                        secTag +
                    '</div>' +
                '</div>' +
                '<div class="build-item-price-col">' +
                    '<div class="build-item-price" style="color:' + (canAfford ? '#4ade80' : '#f87171') + '">' +
                        (seg.type === 'lift' ? '<span style="font-size:9px;color:#94a3b8;font-weight:600;margin-right:2px">from</span>' : '') +
                        '$' + baseCost.toLocaleString() +
                    '</div>' +
                    '<div class="build-item-cta">' + (isSel ? '✓ Selected' : 'Configure ›') + '</div>' +
                '</div>';

            btn.addEventListener('mouseenter', function() {
                if (editorMode) return;
                if (segmentLayers[seg.id] && (!selectedSegId || Number(selectedSegId) !== Number(seg.id))) {
                    segmentLayers[seg.id].setStyle({ weight: 8, opacity: 1, color: '#38bdf8', dashArray: null });
                }
            });

            btn.addEventListener('mouseleave', function() {
                if (editorMode) return;
                if (segmentLayers[seg.id] && (!selectedSegId || Number(selectedSegId) !== Number(seg.id))) {
                    var b = isSegmentBuilt(seg.id);
                    segmentLayers[seg.id].setStyle({
                        color: (b || !buildMode) ? segColor(seg) : '#a855f7',
                        weight: b ? 5 : 6,
                        opacity: b ? 0.95 : 0.85,
                        dashArray: b ? (seg.type === 'lift' ? '10, 5' : null) : '6, 5'
                    });
                }
            });

            btn.addEventListener('click', function() { selectSegment(seg); });
            list.appendChild(btn);
        });
    }

    function selectSegment(seg) {
        deselectSeg();
        selectedSegId = seg.id;
        var targetTab = (seg.type === 'lift') ? 'lift' : 'slope';
        activeTab = targetTab;

        document.querySelectorAll('.build-tab').forEach(function(b) {
            b.classList.toggle('is-active', b.dataset.tab === targetTab);
        });
        var diffFilter = document.getElementById('buildDiffFilter');
        if (diffFilter) diffFilter.style.display = (targetTab === 'slope') ? 'inline-block' : 'none';

        if (segmentLayers[seg.id]) {
            segmentLayers[seg.id].setStyle({ weight: 8, opacity: 1, color: '#38bdf8', dashArray: null });
            try {
                if (typeof segmentLayers[seg.id].getBounds === 'function') {
                    map.panTo(segmentLayers[seg.id].getBounds().getCenter(), { animate: true });
                }
            } catch (e) {}
        }
        document.getElementById('segmentDetail').style.display = 'block';
        document.getElementById('selSegName').textContent = seg.name || 'Unnamed';
        var badgeEl = document.getElementById('selSegBadge');
        if (badgeEl) {
            var c = segColor(seg);
            if (seg.difficulty === 'black') {
                badgeEl.style.background = '#090d16';
                badgeEl.style.color = '#f8fafc';
                badgeEl.style.border = '1px solid #94a3b8';
            } else {
                badgeEl.style.background = c;
                badgeEl.style.color = '#fff';
                badgeEl.style.border = 'none';
            }
            badgeEl.textContent = seg.type === 'lift' ? 'LIFT' : (seg.difficulty ? seg.difficulty.replace('_', ' ') : seg.type);
        }
        document.getElementById('selSegLength').textContent = Math.round(seg.length_meters || 0).toLocaleString() + ' ' + DIST_UNIT;
        var days = (seg.length_meters || 0) < 200 ? 1 : ((seg.length_meters || 0) < 500 ? 2 : 3);
        document.getElementById('selSegTime').textContent = days + ' day' + (days > 1 ? 's' : '');
        var secEl = document.getElementById('selSegSector');
        if (secEl) secEl.textContent = getSectorName(seg.sector);

        document.getElementById('liftOptions').style.display = seg.type === 'lift' ? 'block' : 'none';
        document.getElementById('slopeOptions').style.display = seg.type === 'lift' ? 'none' : 'block';
        if (seg.type !== 'lift') {
            var sr = document.querySelector('input[name="slopeType"][value="' + seg.type + '"]');
            if (sr) sr.checked = true;
        }
        syncOptionCardHighlights();

        document.getElementById('buildDrawer').style.display = 'flex';
        var fab = document.getElementById('buildFab');
        if (fab) fab.style.display = 'none';
        syncDrawerLayoutState();

        if (seg.type === 'lift') updateSeats();
        else updateCost();
        populateList(targetTab);

        var drawerBody = document.getElementById('buildDrawerBody');
        if (drawerBody) drawerBody.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deselectSeg() {
        if (selectedSegId && segmentLayers[selectedSegId]) {
            var s = SEGMENTS.find(function(x) { return Number(x.id) === Number(selectedSegId); });
            if (s) {
                var b = isSegmentBuilt(s.id);
                segmentLayers[selectedSegId].setStyle({
                    color: (b || !buildMode) ? segColor(s) : '#a855f7',
                    weight: b ? 5 : 6,
                    opacity: b ? 0.95 : 0.85,
                    dashArray: b ? (s.type === 'lift' ? '10, 5' : null) : '6, 5'
                });
            }
        }
        selectedSegId = null;
        document.getElementById('segmentDetail').style.display = 'none';
    }

    function updateCost() {
        if (!selectedSegId) return;
        var seg = SEGMENTS.find(function(s) { return Number(s.id) === Number(selectedSegId); });
        if (!seg) return;
        var m = parseFloat(seg.length_meters) || 0, cost;
        if (seg.type === 'lift') {
            var lt = document.querySelector('input[name="liftType"]:checked');
            var st = document.querySelector('input[name="seats"]:checked');
            cost = m * (COST_PER_METER[lt ? lt.value : 'chair_fixed'] || 150) * (SEAT_MULT[st ? parseInt(st.value, 10) : 4] || 1);
        } else {
            cost = m * 50;
        }
        var roundedCost = Math.round(cost);
        var canAfford = USER_CASH >= roundedCost;
        var costEl = document.getElementById('selSegCost');
        if (costEl) {
            costEl.textContent = '$' + roundedCost.toLocaleString();
            costEl.style.color = canAfford ? '#4ade80' : '#f87171';
        }
        var afterEl = document.getElementById('selSegAfterCash');
        if (afterEl) {
            if (canAfford) {
                afterEl.textContent = 'Balance after: $' + (USER_CASH - roundedCost).toLocaleString();
                afterEl.style.color = '#94a3b8';
            } else {
                afterEl.textContent = 'Need $' + (roundedCost - USER_CASH).toLocaleString() + ' more';
                afterEl.style.color = '#f87171';
            }
        }
    }

    function updateSeats() {
        var lt = document.querySelector('input[name="liftType"]:checked');
        var type = lt ? lt.value : 'chair_fixed';
        var opts = SEAT_OPTIONS[type] || [2, 4, 6, 8];
        var grid = document.getElementById('seatGrid');
        grid.innerHTML = '';
        opts.forEach(function(n, i) {
            var label = document.createElement('label');
            label.className = 'seat-pill-btn' + (i === 0 ? ' is-checked' : '');
            label.innerHTML = '<input type="radio" name="seats" value="' + n + '" class="hidden"' + (i === 0 ? ' checked' : '') + '>' +
                '<span>' + n + (type === 'gondola' || type === 'cable_car' ? 'p' : ' seats') + '</span>';
            grid.appendChild(label);
        });
        grid.querySelectorAll('input[name="seats"]').forEach(function(el) {
            el.addEventListener('change', function() {
                grid.querySelectorAll('.seat-pill-btn').forEach(function(lbl) {
                    var r = lbl.querySelector('input[name="seats"]');
                    lbl.classList.toggle('is-checked', !!(r && r.checked));
                });
                updateCost();
            });
        });
        updateCost();
    }

    function doBuild() {
        if (!selectedSegId) return;
        var seg = SEGMENTS.find(function(s) { return Number(s.id) === Number(selectedSegId); });
        if (!seg) return;
        var body = { segment_id: seg.id };
        if (seg.type === 'lift') {
            var lt = document.querySelector('input[name="liftType"]:checked');
            var st = document.querySelector('input[name="seats"]:checked');
            body.lift_type = lt ? lt.value : 'chair_fixed';
            body.seats = st ? parseInt(st.value, 10) : 4;
        } else {
            var sl = document.querySelector('input[name="slopeType"]:checked');
            body.slope_type = sl ? sl.value : 'downhill';
        }

        var btn = document.getElementById('btnBuild');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Building...';
        }

        postJSON('/map/build', body, function(res) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-hammer"></i> Construct';
            }
            if (res && res.success) {
                BUILT_IDS.push(String(seg.id));
                if (res.item) BUILT_ITEMS[seg.id] = res.item;
                if (typeof res.new_cash === 'number') {
                    USER_CASH = res.new_cash;
                    var cashEl = document.getElementById('drawerCashBalance');
                    if (cashEl) cashEl.textContent = '$' + USER_CASH.toLocaleString();
                }
                showToast('Constructed "' + (seg.name || seg.type) + '" for $' + Number(res.cost || 0).toLocaleString() + '!', 'success');
                deselectSeg();
                renderSegments(buildMode);
                populateList(activeTab);
                if (typeof loadTutorial === 'function') loadTutorial();
            } else {
                showToast((res && res.error) ? res.error : 'Construction failed', 'error');
            }
        });
    }

    function postJSON(url, data, callback) {
        var payload = Object.assign({}, data || {});
        payload[CSRF_NAME] = CSRF_HASH;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_HASH
            },
            body: JSON.stringify(payload)
        })
        .then(function(res) {
            return res.json().catch(function() {
                throw new Error('Server returned HTTP ' + res.status);
            });
        })
        .then(function(json) {
            if (json && json.csrf_hash) {
                CSRF_HASH = json.csrf_hash;
            }
            if (callback) callback(json);
        })
        .catch(function(err) {
            console.error('API Error:', err);
            showToast(err.message || 'Request failed', 'error');
            if (callback) callback({ success: false, error: err.message || 'Request failed' });
        });
    }

    function calcLengthMeters(points) {
        if (!points || points.length < 2) return 0;
        var total = 0;
        for (var i = 1; i < points.length; i++) {
            var dy = points[i][0] - points[i - 1][0];
            var dx = points[i][1] - points[i - 1][1];
            total += Math.sqrt(dy * dy + dx * dx);
        }
        return Math.max(1, Math.round(total * 15.0));
    }

    function getSectorName(secId) {
        if (!secId || secId === '0' || secId === 0) return 'Default (0)';
        var sec = SECTORS.find(function(s) { return String(s.id) === String(secId) || s.name === String(secId); });
        return sec ? sec.name : 'Sector #' + secId;
    }

    function escHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showToast(msg, type) {
        var toast = document.getElementById('adminToast');
        var alertEl = document.getElementById('adminToastAlert');
        var msgEl = document.getElementById('adminToastMsg');
        if (!toast || !alertEl || !msgEl) return;
        msgEl.textContent = msg;
        alertEl.className = 'alert py-2 px-3.5 text-xs shadow-xl font-semibold ' +
            (type === 'error' ? 'alert-error' : (type === 'warning' ? 'alert-warning' : (type === 'info' ? 'alert-info' : 'alert-success')));
        toast.classList.remove('opacity-0', 'translate-y-[-10px]');
        toast.classList.add('opacity-100', 'translate-y-0');
        if (window._toastTimeout) clearTimeout(window._toastTimeout);
        window._toastTimeout = setTimeout(function() {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', 'translate-y-[-10px]');
        }, 3200);
    }

    /* ======================================================================
     * ADMIN MAP STUDIO: LIFT, SLOPE & SECTOR BOUNDARY DRAWING ENGINE
     * ====================================================================== */

    function pushUndoState() {
        undoStack.push(editorPoints.map(function(p) { return [p[0], p[1]]; }));
        if (undoStack.length > 40) undoStack.shift();
    }

    /**
     * Magnetic endpoint & vertex snapping:
     * Finds the closest existing segment vertex within threshold (in CRS map units).
     */
    function getSnappedLatLng(latlng, altPressed) {
        currentSnapTarget = null;
        if (!snapEnabled || altPressed || editorMode === 'sector_boundary') {
            hideSnapIndicator();
            return latlng;
        }

        // Convert 14 screen pixels into current zoom's CRS coordinate distance
        var p1 = map.latLngToContainerPoint(latlng);
        var p2 = L.point(p1.x + 14, p1.y);
        var ll2 = map.containerPointToLatLng(p2);
        var maxDist = Math.abs(ll2.lng - latlng.lng) || 5;

        var bestDist = maxDist;
        var bestPt = null;
        var bestLabel = '';

        SEGMENTS.forEach(function(seg) {
            if (editingSegId && Number(seg.id) === Number(editingSegId)) return;
            var pts = parsePoints(seg.points);
            if (!pts.length) return;
            pts.forEach(function(pt, idx) {
                var dy = pt[0] - latlng.lat;
                var dx = pt[1] - latlng.lng;
                var d = Math.sqrt(dy * dy + dx * dx);
                // Prioritize start/end stations slightly
                var isEndPt = (idx === 0 || idx === pts.length - 1);
                var effDist = isEndPt ? d * 0.85 : d;
                if (effDist < bestDist) {
                    bestDist = effDist;
                    bestPt = pt;
                    bestLabel = (seg.name || seg.type) + (idx === 0 ? ' (Start)' : (idx === pts.length - 1 ? ' (End)' : ''));
                }
            });
        });

        if (bestPt) {
            currentSnapTarget = { lat: bestPt[0], lng: bestPt[1], label: bestLabel };
            showSnapIndicator([bestPt[0], bestPt[1]], bestLabel);
            return L.latLng(bestPt[0], bestPt[1]);
        }

        hideSnapIndicator();
        return latlng;
    }

    function showSnapIndicator(pt, label) {
        if (!snapMarker) {
            var icon = L.divIcon({
                className: 'snap-indicator-wrap',
                html: '<div class="snap-indicator-ring"></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
            snapMarker = L.marker(pt, { icon: icon, interactive: false, zIndexOffset: 2000 }).addTo(map);
        } else {
            snapMarker.setLatLng(pt);
        }
        var pill = document.getElementById('snapStatusPill');
        if (pill) {
            pill.style.display = 'inline-flex';
            pill.textContent = '🧲 Snapped: ' + label;
        }
    }

    function hideSnapIndicator() {
        if (snapMarker) {
            map.removeLayer(snapMarker);
            snapMarker = null;
        }
        var pill = document.getElementById('snapStatusPill');
        if (pill) pill.style.display = 'none';
    }

    function clearEditorMarkers() {
        vertexMarkers.forEach(function(m) { map.removeLayer(m); });
        vertexMarkers = [];
        midpointMarkers.forEach(function(m) { map.removeLayer(m); });
        midpointMarkers = [];
        if (rubberBandLine) {
            map.removeLayer(rubberBandLine);
            rubberBandLine = null;
        }
        hideSnapIndicator();
    }

    function deleteVertexAt(index) {
        var minPts = (editorMode === 'sector_boundary') ? 3 : 2;
        if (editorMode === 'draw' && editorPoints.length <= 1) {
            editorPoints = [];
            selectedVertexIdx = -1;
            updateEditorDisplay(true);
            return;
        }
        if (editorPoints.length <= minPts) {
            showToast('A ' + (editorMode === 'sector_boundary' ? 'sector boundary requires at least 3' : 'line requires at least 2') + ' points', 'warning');
            return;
        }
        pushUndoState();
        editorPoints.splice(index, 1);
        selectedVertexIdx = -1;
        updateEditorDisplay(true);
    }

    function createVertexMarker(latlng, index, total, accentColor) {
        var isStart = (index === 0);
        var isEnd   = (total > 1 && index === total - 1 && editorMode !== 'sector_boundary');
        var isSel   = (index === selectedVertexIdx);

        var classes = 'custom-vertex-handle' +
            (isStart ? ' is-start' : (isEnd ? ' is-end' : '')) +
            (isSel ? ' is-selected' : '');
        var label = isStart ? 'S' : (isEnd ? 'E' : '');
        var size  = (isStart || isEnd) ? 20 : 16;
        var anchor = size / 2;

        var icon = L.divIcon({
            className: 'custom-vertex-wrap',
            html: '<div class="' + classes + '" style="' + (!isStart && !isEnd ? 'border-color:' + accentColor : '') + '">' + label + '</div>',
            iconSize: [size, size],
            iconAnchor: [anchor, anchor]
        });

        var marker = L.marker(latlng, {
            icon: icon,
            draggable: true,
            zIndexOffset: isStart || isEnd ? 1200 : 1000
        });

        var ptLabel = isStart ? 'Start Station (Point #1)' : (isEnd ? 'End Station (Point #' + (index + 1) + ')' : 'Vertex #' + (index + 1));
        marker.bindTooltip(ptLabel + ' • Drag to move • Right-click or Alt+click to delete', { direction: 'top', offset: [0, -8] });

        marker.on('dragstart', function() {
            pushUndoState();
            selectedVertexIdx = index;
        });

        marker.on('drag', function(e) {
            var rawLl = marker.getLatLng();
            var altKey = e.originalEvent && e.originalEvent.altKey;
            var snapped = getSnappedLatLng(rawLl, altKey);
            editorPoints[index] = [snapped.lat, snapped.lng];
            updateEditorDisplay(false);
        });

        marker.on('dragend', function() {
            if (currentSnapTarget) {
                editorPoints[index] = [currentSnapTarget.lat, currentSnapTarget.lng];
            }
            hideSnapIndicator();
            updateEditorDisplay(true);
        });

        marker.on('click', function(e) {
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            var orig = e.originalEvent;
            if (orig && (orig.altKey || orig.shiftKey)) {
                deleteVertexAt(index);
                return;
            }
            selectedVertexIdx = index;
            updateEditorDisplay(true);
        });

        marker.on('contextmenu', function(e) {
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            deleteVertexAt(index);
        });

        return marker;
    }

    function createMidpointMarker(index, nextIndex, accentColor) {
        var p1 = editorPoints[index];
        var p2 = editorPoints[nextIndex];
        var midLat = (p1[0] + p2[0]) / 2;
        var midLng = (p1[1] + p2[1]) / 2;

        var icon = L.divIcon({
            className: 'custom-midpoint-wrap',
            html: '<div class="custom-midpoint-handle" style="background:' + accentColor + '">+</div>',
            iconSize: [12, 12],
            iconAnchor: [6, 6]
        });

        var marker = L.marker([midLat, midLng], {
            icon: icon,
            draggable: true,
            zIndexOffset: 900
        });

        marker.bindTooltip('Click or drag to insert a bend point here', { direction: 'top', offset: [0, -6] });

        var insertedIndex = -1;

        marker.on('click', function(e) {
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            pushUndoState();
            var insertAt = index + 1;
            editorPoints.splice(insertAt, 0, [midLat, midLng]);
            selectedVertexIdx = insertAt;
            updateEditorDisplay(true);
            showToast('Inserted vertex #' + (insertAt + 1), 'info');
        });

        marker.on('dragstart', function() {
            pushUndoState();
            insertedIndex = index + 1;
            var ll = marker.getLatLng();
            editorPoints.splice(insertedIndex, 0, [ll.lat, ll.lng]);
            selectedVertexIdx = insertedIndex;
        });

        marker.on('drag', function(e) {
            if (insertedIndex !== -1) {
                var rawLl = marker.getLatLng();
                var altKey = e.originalEvent && e.originalEvent.altKey;
                var snapped = getSnappedLatLng(rawLl, altKey);
                editorPoints[insertedIndex] = [snapped.lat, snapped.lng];
                updateEditorDisplay(false);
            }
        });

        marker.on('dragend', function() {
            if (insertedIndex !== -1 && currentSnapTarget) {
                editorPoints[insertedIndex] = [currentSnapTarget.lat, currentSnapTarget.lng];
            }
            insertedIndex = -1;
            hideSnapIndicator();
            updateEditorDisplay(true);
        });

        return marker;
    }

    function getActiveEditorAccentColor() {
        if (editorMode === 'sector_boundary') {
            var sec = SECTORS.find(function(s) { return Number(s.id) === Number(editingSectorId); });
            return (sec && sec.color) ? sec.color : '#38bdf8';
        }
        if (editorType === 'lift') return COLORS.lift;
        var slopeType = document.getElementById('toolSlopeType') ? document.getElementById('toolSlopeType').value : 'downhill';
        var diff = document.getElementById('toolSegDiff') ? document.getElementById('toolSegDiff').value : 'blue';
        if (slopeType === 'snowpark') return COLORS.snowpark;
        return COLORS[diff] || COLORS.blue;
    }

    function updateEditorDisplay(rebuildMarkers) {
        var accentColor = getActiveEditorAccentColor();

        if (editorLine) {
            map.removeLayer(editorLine);
            editorLine = null;
        }

        if (editorPoints.length > 0) {
            if (editorMode === 'sector_boundary' && editorPoints.length >= 3) {
                editorLine = L.polygon(editorPoints, {
                    color: accentColor,
                    weight: 3,
                    opacity: 0.95,
                    fillColor: accentColor,
                    fillOpacity: 0.2,
                    dashArray: '6, 4',
                    interactive: false
                }).addTo(map);
            } else {
                editorLine = L.polyline(editorPoints, {
                    color: accentColor,
                    weight: 5,
                    opacity: 0.95,
                    dashArray: '8, 6',
                    interactive: false
                }).addTo(map);
            }
        }

        var lengthM = calcLengthMeters(editorPoints);
        var lenEl = document.getElementById('toolLiveLength');
        var ptsEl = document.getElementById('toolPointCount');
        if (lenEl) lenEl.textContent = lengthM.toLocaleString();
        if (ptsEl) ptsEl.textContent = editorPoints.length;

        if (rebuildMarkers) {
            clearEditorMarkers();
            var total = editorPoints.length;
            editorPoints.forEach(function(pt, idx) {
                var vm = createVertexMarker(pt, idx, total, accentColor);
                vm.addTo(map);
                vertexMarkers.push(vm);
            });
            if (total >= 2) {
                for (var i = 0; i < total - 1; i++) {
                    var mm = createMidpointMarker(i, i + 1, accentColor);
                    mm.addTo(map);
                    midpointMarkers.push(mm);
                }
                if (editorMode === 'sector_boundary' && total >= 3) {
                    var closeMm = createMidpointMarker(total - 1, 0, accentColor);
                    closeMm.addTo(map);
                    midpointMarkers.push(closeMm);
                }
            }
        }
    }

    function configureToolbarForType(isLift, slopeSubType, difficultyVal, sectorVal, segName) {
        var slopeTypeEl = document.getElementById('toolSlopeType');
        var diffEl      = document.getElementById('toolSegDiff');
        var secEl       = document.getElementById('toolSegSector');
        var nameEl      = document.getElementById('toolSegName');

        if (nameEl) nameEl.value = segName || '';
        if (secEl)  secEl.value  = String(Number(sectorVal || 0));

        if (!isLift) {
            slopeTypeEl.style.display = 'inline-block';
            slopeTypeEl.value = slopeSubType || 'downhill';
            diffEl.style.display = 'inline-block';
            diffEl.value = difficultyVal || 'blue';
        } else {
            slopeTypeEl.style.display = 'none';
            diffEl.style.display = 'none';
        }
    }

    function setAppendMode(enabled) {
        appendInEditMode = !!enabled;
        var btn = document.getElementById('toolBtnAppendToggle');
        var txt = document.getElementById('toolAppendText');
        if (btn && txt) {
            txt.textContent = 'Append: ' + (appendInEditMode ? 'ON' : 'OFF');
            btn.className = 'btn btn-xs gap-1 ' + (appendInEditMode ? 'btn-info font-bold' : 'btn-info btn-outline');
        }
        var mapEl = document.getElementById('map');
        if (mapEl) {
            if (editorMode === 'draw' || editorMode === 'sector_boundary' || appendInEditMode) {
                mapEl.classList.add('drawing-cursor');
            } else {
                mapEl.classList.remove('drawing-cursor');
            }
        }
    }

    function nextSuggestedName(type) {
        var isLift = (type === 'lift');
        var prefix = isLift ? 'Lift Line ' : 'Slope Path ';
        var maxNum = SEGMENTS.filter(function(s) {
            return isLift ? s.type === 'lift' : s.type !== 'lift';
        }).length + 1;
        while (SEGMENTS.some(function(s) { return (s.name || '').toLowerCase() === (prefix + maxNum).toLowerCase(); })) {
            maxNum++;
        }
        return prefix + maxNum;
    }

    function startDraw(type, preserveSettings) {
        var prevSec  = document.getElementById('toolSegSector') ? document.getElementById('toolSegSector').value : null;
        var prevDiff = document.getElementById('toolSegDiff') ? document.getElementById('toolSegDiff').value : 'blue';
        var prevSub  = document.getElementById('toolSlopeType') ? document.getElementById('toolSlopeType').value : 'downhill';

        if (editorMode) cancelEditor(true);
        editorMode = 'draw';
        editingSegId = null;
        editingSectorId = null;
        editorType = (type === 'lift') ? 'lift' : 'slope';
        editorPoints = [];
        undoStack = [];
        selectedVertexIdx = -1;

        document.getElementById('toolIdleView').style.display = 'none';
        document.getElementById('toolActiveView').style.display = 'flex';
        document.getElementById('toolHelperHint').style.display = 'flex';
        document.getElementById('toolBtnAppendToggle').style.display = 'none';
        document.getElementById('toolBtnSaveNext').style.display = 'inline-flex';
        document.getElementById('toolSegName').style.display = 'inline-block';
        document.getElementById('toolSegSector').style.display = 'inline-block';

        var modeBadge = document.getElementById('toolModeBadge');
        modeBadge.className = 'badge text-xs font-bold gap-1.5 py-2.5 px-2.5 ' + (editorType === 'lift' ? 'badge-warning' : 'badge-success');
        document.getElementById('toolModeText').textContent = 'New ' + (editorType === 'lift' ? 'Lift' : 'Slope');

        var activeSec = preserveSettings && prevSec !== null
            ? prevSec
            : (document.getElementById('toolActiveSector') ? document.getElementById('toolActiveSector').value : '0');

        configureToolbarForType(
            editorType === 'lift',
            preserveSettings ? prevSub : 'downhill',
            preserveSettings ? prevDiff : 'blue',
            activeSec,
            nextSuggestedName(editorType)
        );

        setAppendMode(true);
        map.doubleClickZoom.disable();

        updateEditorDisplay(true);
        showToast('Drawing ' + (editorType === 'lift' ? 'Lift (Bottom → Top)' : 'Slope (Top → Bottom)') + ': click map to place points', 'info');
    }

    function startEditSegment(seg) {
        if (editorMode) cancelEditor(true);
        editorMode = 'edit';
        editingSegId = seg.id;
        editingSectorId = null;
        // CRITICAL FIX: normalize editorType to 'lift' or 'slope' so slope subtypes ('downhill', etc.) preserve difficulty & subtype!
        editorType = (seg.type === 'lift') ? 'lift' : 'slope';
        editorPoints = parsePoints(seg.points);
        undoStack = [];
        selectedVertexIdx = -1;

        // Re-render static segments so the edited segment is hidden from static layer
        renderSegments(buildMode);

        document.getElementById('toolIdleView').style.display = 'none';
        document.getElementById('toolActiveView').style.display = 'flex';
        document.getElementById('toolHelperHint').style.display = 'flex';
        document.getElementById('toolBtnAppendToggle').style.display = 'inline-flex';
        document.getElementById('toolBtnSaveNext').style.display = 'none';
        document.getElementById('toolSegName').style.display = 'inline-block';
        document.getElementById('toolSegSector').style.display = 'inline-block';

        var modeBadge = document.getElementById('toolModeBadge');
        modeBadge.className = 'badge text-xs font-bold gap-1.5 py-2.5 px-2.5 ' + (editorType === 'lift' ? 'badge-warning' : 'badge-success');
        document.getElementById('toolModeText').textContent = 'Editing ' + (editorType === 'lift' ? 'Lift' : 'Slope');

        configureToolbarForType(
            editorType === 'lift',
            seg.type !== 'lift' ? seg.type : 'downhill',
            seg.difficulty || 'blue',
            seg.sector || 0,
            seg.name || ''
        );

        // In edit mode, default Append to OFF so clicking the map to pan doesn't accidentally add stray vertices
        setAppendMode(false);
        map.doubleClickZoom.disable();

        updateEditorDisplay(true);
        showToast('Editing "' + (seg.name || seg.type) + '": drag points to move, drag/click + midpoints to bend', 'info');
    }

    function startEditSectorBoundary(sec) {
        if (editorMode) cancelEditor(true);
        editorMode = 'sector_boundary';
        editingSectorId = sec.id;
        editingSegId = null;
        editorPoints = parsePoints(sec.boundary_points);
        undoStack = [];
        selectedVertexIdx = -1;
        showSectorPolygons = true;
        renderSectorPolygons();

        document.getElementById('toolIdleView').style.display = 'none';
        document.getElementById('toolActiveView').style.display = 'flex';
        document.getElementById('toolHelperHint').style.display = 'flex';
        document.getElementById('toolBtnAppendToggle').style.display = 'none';
        document.getElementById('toolBtnSaveNext').style.display = 'none';
        document.getElementById('toolSegName').style.display = 'none';
        document.getElementById('toolSegSector').style.display = 'none';
        document.getElementById('toolSlopeType').style.display = 'none';
        document.getElementById('toolSegDiff').style.display = 'none';

        var modeBadge = document.getElementById('toolModeBadge');
        modeBadge.className = 'badge badge-info text-xs font-bold gap-1.5 py-2.5 px-2.5';
        document.getElementById('toolModeText').textContent = 'Boundary: ' + sec.name;

        setAppendMode(true);
        map.doubleClickZoom.disable();

        updateEditorDisplay(true);
        showToast('Click around the mountain to outline "' + sec.name + '", then click Save', 'info');
    }

    function cancelEditor(skipRender) {
        editorMode = null;
        editingSegId = null;
        editingSectorId = null;
        editorPoints = [];
        undoStack = [];
        selectedVertexIdx = -1;
        setAppendMode(false);

        if (editorLine) {
            map.removeLayer(editorLine);
            editorLine = null;
        }
        clearEditorMarkers();

        map.doubleClickZoom.enable();

        var activeView = document.getElementById('toolActiveView');
        var idleView   = document.getElementById('toolIdleView');
        var hintView   = document.getElementById('toolHelperHint');
        if (activeView) activeView.style.display = 'none';
        if (hintView)   hintView.style.display   = 'none';
        if (idleView)   idleView.style.display   = 'flex';

        if (!skipRender) {
            renderSegments(buildMode);
        }
    }

    function saveEditor(drawNextAfterSave) {
        if (editorMode === 'sector_boundary') {
            if (editorPoints.length < 3) {
                showToast('Please place at least 3 points for a sector boundary polygon', 'warning');
                return;
            }
            var secId = editingSectorId;
            postJSON('/map/sector/boundary/' + secId, { id: secId, boundary_points: editorPoints }, function(res) {
                if (res && res.success) {
                    var sec = SECTORS.find(function(s) { return Number(s.id) === Number(secId); });
                    if (sec) sec.boundary_points = JSON.stringify(editorPoints);
                    showToast('Sector boundary saved!', 'success');
                    cancelEditor(false);
                    renderInspectorSectors();
                }
            });
            return;
        }

        if (editorPoints.length < 2) {
            showToast('Please place at least 2 points for a lift or slope', 'warning');
            return;
        }

        var name = document.getElementById('toolSegName').value.trim();
        if (!name) {
            name = nextSuggestedName(editorType);
        }

        var sectorId   = parseInt(document.getElementById('toolSegSector').value || '0', 10) || 0;
        var isLift     = (editorType === 'lift');
        var segType    = isLift ? 'lift' : (document.getElementById('toolSlopeType').value || 'downhill');
        var difficulty = isLift ? '' : (document.getElementById('toolSegDiff').value || 'blue');
        var lengthM    = calcLengthMeters(editorPoints);

        // Keep the idle toolbar's active sector synced with the last used sector
        var idleSecEl = document.getElementById('toolActiveSector');
        if (idleSecEl) idleSecEl.value = String(sectorId);

        var payload = {
            name: name,
            type: segType,
            sector: sectorId,
            difficulty: difficulty,
            length_meters: lengthM,
            points: editorPoints
        };
        if (editingSegId) {
            payload.id = editingSegId;
        }

        var btnSave = document.getElementById('toolBtnSave');
        if (btnSave) {
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Saving...';
        }

        var currentEditorType = editorType;

        postJSON('/map/segment', payload, function(res) {
            if (btnSave) {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fa-solid fa-check"></i> Save';
            }

            if (res && res.success) {
                var savedId = Number(res.id || editingSegId);
                var updatedSeg = {
                    id: savedId,
                    name: name,
                    type: segType,
                    sector: sectorId,
                    difficulty: difficulty,
                    length_meters: lengthM,
                    points: JSON.stringify(editorPoints),
                    active: 1
                };

                if (editingSegId) {
                    var idx = SEGMENTS.findIndex(function(s) { return Number(s.id) === Number(editingSegId); });
                    if (idx !== -1) {
                        SEGMENTS[idx] = updatedSeg;
                    }
                    showToast('Updated "' + name + '" (' + lengthM + ' ' + DIST_UNIT + ')', 'success');
                } else {
                    SEGMENTS.push(updatedSeg);
                    showToast('Created "' + name + '" (' + lengthM + ' ' + DIST_UNIT + ')', 'success');
                }

                updateHeaderCounts();
                renderInspectorSegments();
                if (document.getElementById('buildDrawer').style.display !== 'none') {
                    populateList(activeTab);
                }

                if (drawNextAfterSave) {
                    cancelEditor(false);
                    startDraw(currentEditorType, true);
                } else {
                    cancelEditor(false);
                }
            } else {
                showToast((res && res.error) ? res.error : 'Failed to save segment', 'error');
            }
        });
    }

    function undoLastAction() {
        if (!editorMode) return;
        if (undoStack.length > 0) {
            editorPoints = undoStack.pop();
            selectedVertexIdx = -1;
            updateEditorDisplay(true);
        } else if (editorPoints.length > 0) {
            editorPoints.pop();
            selectedVertexIdx = -1;
            updateEditorDisplay(true);
        }
    }

    function reverseEditorPoints() {
        if (!editorMode || editorPoints.length < 2) return;
        pushUndoState();
        editorPoints.reverse();
        updateEditorDisplay(true);
        showToast('Reversed Start (S) and End (E) direction', 'info');
    }

    function openAdminSegmentPopup(seg, latlng) {
        var c = segColor(seg);
        var secName = getSectorName(seg.sector);
        var diffBadge = seg.difficulty ? '<span class="badge badge-xs text-[10px] font-bold uppercase" style="background:' + c + ';color:#fff">' + escHtml(seg.difficulty.replace('_', ' ')) + '</span>' : '';
        var popupContent = document.createElement('div');
        popupContent.className = 'admin-segment-popover p-1 text-xs';
        popupContent.innerHTML =
            '<div class="flex items-center justify-between gap-2 mb-2 pb-1.5 border-b border-white/10">' +
                '<div class="font-bold text-sm flex items-center gap-1.5">' +
                    (seg.type === 'lift' ? '<i class="fa-solid fa-elevator text-warning"></i>' : '<i class="fa-solid fa-person-skiing text-success"></i>') +
                    ' <span class="truncate max-w-[145px]">' + escHtml(seg.name || 'Unnamed') + '</span>' +
                '</div>' +
                diffBadge +
            '</div>' +
            '<div class="space-y-1 mb-3 text-base-content/80 text-[11px]">' +
                '<div class="flex items-center justify-between"><span>ID / Type:</span> <span class="font-mono">#' + seg.id + ' • <b class="capitalize">' + escHtml(seg.type) + '</b></span></div>' +
                '<div class="flex items-center justify-between"><span>Length:</span> <span class="font-mono font-bold">' + Math.round(seg.length_meters || 0) + ' ' + DIST_UNIT + '</span></div>' +
                '<div class="flex items-center justify-between"><span>Sector:</span> <span class="badge badge-outline badge-xs">' + escHtml(secName) + '</span></div>' +
            '</div>' +
            '<div class="grid grid-cols-2 gap-1.5 mb-1.5">' +
                '<button type="button" class="btn btn-xs btn-primary font-bold edit-path-btn gap-1"><i class="fa-solid fa-pen-to-square"></i> Edit Path</button>' +
                '<button type="button" class="btn btn-xs btn-error btn-outline font-bold delete-seg-btn gap-1"><i class="fa-solid fa-trash"></i> Delete</button>' +
            '</div>' +
            '<button type="button" class="btn btn-xs btn-ghost border border-white/15 w-full font-semibold build-as-player-btn gap-1"><i class="fa-solid fa-hammer text-[10px]"></i> Open in Build Drawer</button>';

        popupContent.querySelector('.edit-path-btn').addEventListener('click', function() {
            map.closePopup();
            startEditSegment(seg);
        });
        popupContent.querySelector('.delete-seg-btn').addEventListener('click', function() {
            map.closePopup();
            confirmDeleteSegment(seg);
        });
        popupContent.querySelector('.build-as-player-btn').addEventListener('click', function() {
            map.closePopup();
            selectSegment(seg);
        });

        L.popup({ minWidth: 220, maxWidth: 280, className: 'admin-leaflet-popup' })
            .setLatLng(latlng)
            .setContent(popupContent)
            .openOn(map);
    }

    function confirmDeleteSegment(seg) {
        if (!confirm('Delete "' + (seg.name || 'Segment #' + seg.id) + '"? This cannot be undone.')) {
            return;
        }
        postJSON('/map/segment/delete/' + seg.id, {}, function(res) {
            if (res && res.success) {
                SEGMENTS = SEGMENTS.filter(function(s) { return Number(s.id) !== Number(seg.id); });
                updateHeaderCounts();
                renderSegments(buildMode);
                renderInspectorSegments();
                if (document.getElementById('buildDrawer').style.display !== 'none') {
                    populateList(activeTab);
                }
                showToast('Deleted "' + (seg.name || 'Segment') + '"', 'success');
            } else {
                showToast((res && res.error) ? res.error : 'Failed to delete segment', 'error');
            }
        });
    }

    /* ======================================================================
     * ADMIN INSPECTOR DRAWER (SEGMENTS DIRECTORY & SECTORS MANAGER)
     * ====================================================================== */

    function renderInspectorSectors() {
        var list = document.getElementById('sectorList');
        if (!list) return;
        if (!SECTORS.length) {
            list.innerHTML = '<p style="font-size:12px;color:#64748b;padding:8px 0">No sectors created for this mountain yet.</p>';
            return;
        }
        list.innerHTML = '';
        SECTORS.forEach(function(sec) {
            var bp = parsePoints(sec.boundary_points);
            var hasBoundary = bp.length >= 3;
            var segCountInSec = SEGMENTS.filter(function(s) { return Number(s.sector) === Number(sec.id); }).length;

            var row = document.createElement('div');
            row.className = 'build-item-card';
            row.style.cursor = 'default';
            row.style.marginBottom = '4px';
            row.innerHTML =
                '<div class="build-item-emblem" style="width:30px;height:30px;background:' + escHtml(sec.color || '#3b82f6') + '22;border:1.5px solid ' + escHtml(sec.color || '#3b82f6') + ';color:' + escHtml(sec.color || '#3b82f6') + '">' +
                    '<i class="fa-solid fa-layer-group text-xs"></i>' +
                '</div>' +
                '<div class="build-item-info">' +
                    '<div class="build-item-title-row">' +
                        '<span class="build-item-name">' + escHtml(sec.name) + '</span>' +
                        '<span style="font-size:10px;color:#64748b;font-family:monospace">#' + sec.id + '</span>' +
                    '</div>' +
                    '<div class="build-item-meta">' +
                        '<span>' + segCountInSec + ' runs</span>' +
                        '<span>• ' + (hasBoundary ? '✓ Boundary set' : 'No boundary') + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="flex items-center gap-1 flex-shrink-0">' +
                    '<button type="button" class="btn btn-ghost btn-xs draw-sec-boundary" title="Draw/Edit Sector Polygon Boundary on Map"><i class="fa-solid fa-draw-polygon ' + (hasBoundary ? 'text-info' : 'opacity-50') + '"></i></button>' +
                    '<button type="button" class="btn btn-ghost btn-xs toggle-sec-vis" title="Toggle Visibility"><i class="fa-solid fa-eye' + (Number(sec.visible) ? ' text-success' : '-slash opacity-40') + '"></i></button>' +
                    '<button type="button" class="btn btn-ghost btn-xs text-error delete-sec-btn" title="Delete Sector"><i class="fa-solid fa-trash"></i></button>' +
                '</div>';

            row.querySelector('.draw-sec-boundary').addEventListener('click', function() {
                document.getElementById('adminInspectorDrawer').style.display = 'none';
                syncDrawerLayoutState();
                startEditSectorBoundary(sec);
            });
            row.querySelector('.toggle-sec-vis').addEventListener('click', function() {
                postJSON('/map/sector/toggle/' + sec.id, {}, function(res) {
                    if (res && res.success) {
                        sec.visible = res.visible ? 1 : 0;
                        renderInspectorSectors();
                        showToast(sec.name + ' is now ' + (sec.visible ? 'visible' : 'hidden'), 'info');
                    }
                });
            });
            row.querySelector('.delete-sec-btn').addEventListener('click', function() {
                if (!confirm('Delete sector "' + sec.name + '"? Segments in this sector will be reset to Default (0).')) return;
                postJSON('/map/sector/delete/' + sec.id, {}, function(res) {
                    if (res && res.success) {
                        SECTORS = SECTORS.filter(function(s) { return Number(s.id) !== Number(sec.id); });
                        SEGMENTS.forEach(function(sg) {
                            if (Number(sg.sector) === Number(sec.id)) sg.sector = 0;
                        });
                        renderInspectorSectors();
                        renderInspectorSegments();
                        renderSectorPolygons();
                        showToast('Sector deleted', 'success');
                    }
                });
            });

            list.appendChild(row);
        });
    }

    function renderInspectorSegments() {
        var list = document.getElementById('inspectorSegmentList');
        if (!list) return;

        var q  = (document.getElementById('inspectorSearch') ? document.getElementById('inspectorSearch').value : '').trim().toLowerCase();
        var ft = (document.getElementById('inspectorFilterType') ? document.getElementById('inspectorFilterType').value : 'all');

        var filtered = SEGMENTS.filter(function(s) {
            if (ft === 'lift' && s.type !== 'lift') return false;
            if (ft === 'slope' && s.type === 'lift') return false;
            if (ft === 'unassigned' && Number(s.sector || 0) !== 0) return false;
            if (q && (s.name || '').toLowerCase().indexOf(q) === -1) return false;
            return true;
        });

        if (!filtered.length) {
            list.innerHTML = '<p style="font-size:12px;color:#64748b;text-align:center;padding:24px 0">No segments match filter</p>';
            return;
        }

        list.innerHTML = '';
        filtered.forEach(function(seg) {
            var secName = getSectorName(seg.sector);
            var row = document.createElement('div');
            row.className = 'build-item-card';
            row.style.marginBottom = '4px';
            row.innerHTML =
                '<div class="flex items-center gap-2.5 min-w-0 cursor-pointer flex-1 seg-zoom-trigger">' +
                    buildEmblemHtml(seg) +
                    '<div class="build-item-info">' +
                        '<div class="build-item-title-row">' +
                            '<span class="build-item-name">' + escHtml(seg.name || 'Unnamed') + '</span>' +
                            buildDiffPillHtml(seg) +
                        '</div>' +
                        '<div class="build-item-meta">' +
                            '<span>' + Math.round(seg.length_meters || 0).toLocaleString() + ' ' + DIST_UNIT + '</span>' +
                            '<span>• ' + escHtml(secName) + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="flex items-center gap-1 flex-shrink-0">' +
                    '<button type="button" class="btn btn-ghost btn-xs seg-edit-trigger" title="Edit Path & Details"><i class="fa-solid fa-pen-to-square text-info"></i></button>' +
                    '<button type="button" class="btn btn-ghost btn-xs text-error seg-del-trigger" title="Delete Segment"><i class="fa-solid fa-trash"></i></button>' +
                '</div>';

            row.querySelector('.seg-zoom-trigger').addEventListener('click', function() {
                var layer = segmentLayers[seg.id];
                if (layer && typeof layer.getBounds === 'function') {
                    map.fitBounds(layer.getBounds(), { maxZoom: 2.5, padding: [60, 60], animate: true });
                    openAdminSegmentPopup(seg, layer.getBounds().getCenter());
                }
            });
            row.querySelector('.seg-edit-trigger').addEventListener('click', function() {
                var layer = segmentLayers[seg.id];
                if (layer && typeof layer.getBounds === 'function') {
                    map.fitBounds(layer.getBounds(), { maxZoom: 2, padding: [80, 80], animate: true });
                }
                startEditSegment(seg);
            });
            row.querySelector('.seg-del-trigger').addEventListener('click', function() {
                confirmDeleteSegment(seg);
            });

            list.appendChild(row);
        });
    }

    function bindAdmin() {
        var btnLift       = document.getElementById('btnNewLift');
        var btnSlope      = document.getElementById('btnNewSlope');
        var btnSave       = document.getElementById('toolBtnSave');
        var btnSaveNext   = document.getElementById('toolBtnSaveNext');
        var btnUndo       = document.getElementById('toolBtnUndo');
        var btnReverse    = document.getElementById('toolBtnReverse');
        var btnAppend     = document.getElementById('toolBtnAppendToggle');
        var btnCancel     = document.getElementById('toolBtnCancel');
        var btnSnap       = document.getElementById('btnToggleSnap');
        var btnSectors    = document.getElementById('btnToggleSectors');
        var btnInspector  = document.getElementById('btnOpenInspector');
        var closeInspBtn  = document.getElementById('closeInspectorBtn');

        if (btnLift)      btnLift.addEventListener('click', function() { startDraw('lift', false); });
        if (btnSlope)     btnSlope.addEventListener('click', function() { startDraw('slope', false); });
        if (btnSave)      btnSave.addEventListener('click', function() { saveEditor(false); });
        if (btnSaveNext)  btnSaveNext.addEventListener('click', function() { saveEditor(true); });
        if (btnUndo)      btnUndo.addEventListener('click', undoLastAction);
        if (btnReverse)   btnReverse.addEventListener('click', reverseEditorPoints);
        if (btnAppend)    btnAppend.addEventListener('click', function() { setAppendMode(!appendInEditMode); });
        if (btnCancel)    btnCancel.addEventListener('click', function() { cancelEditor(false); });

        if (btnSnap) {
            btnSnap.addEventListener('click', function() {
                snapEnabled = !snapEnabled;
                btnSnap.innerHTML = '<i class="fa-solid fa-magnet"></i> Snap: ' + (snapEnabled ? 'ON' : 'OFF');
                btnSnap.className = 'btn btn-xs gap-1 font-semibold ' + (snapEnabled ? 'btn-info btn-outline' : 'btn-ghost opacity-60');
                if (!snapEnabled) hideSnapIndicator();
            });
        }

        if (btnSectors) {
            btnSectors.addEventListener('click', function() {
                showSectorPolygons = !showSectorPolygons;
                btnSectors.classList.toggle('btn-warning', showSectorPolygons);
                btnSectors.classList.toggle('btn-ghost', !showSectorPolygons);
                renderSectorPolygons();
            });
        }

        if (btnInspector) {
            btnInspector.addEventListener('click', function() {
                closeDrawer();
                var insp = document.getElementById('adminInspectorDrawer');
                if (insp) {
                    insp.style.display = (insp.style.display === 'none' ? 'flex' : 'none');
                    syncDrawerLayoutState();
                    renderInspectorSectors();
                    renderInspectorSegments();
                }
            });
        }
        if (closeInspBtn) {
            closeInspBtn.addEventListener('click', function() {
                document.getElementById('adminInspectorDrawer').style.display = 'none';
                syncDrawerLayoutState();
            });
        }

        var inspNewLift  = document.getElementById('inspectorNewLiftBtn');
        var inspNewSlope = document.getElementById('inspectorNewSlopeBtn');
        if (inspNewLift) {
            inspNewLift.addEventListener('click', function() {
                document.getElementById('adminInspectorDrawer').style.display = 'none';
                syncDrawerLayoutState();
                startDraw('lift', false);
            });
        }
        if (inspNewSlope) {
            inspNewSlope.addEventListener('click', function() {
                document.getElementById('adminInspectorDrawer').style.display = 'none';
                syncDrawerLayoutState();
                startDraw('slope', false);
            });
        }

        var inspSearch = document.getElementById('inspectorSearch');
        var inspFilter = document.getElementById('inspectorFilterType');
        if (inspSearch) inspSearch.addEventListener('input', renderInspectorSegments);
        if (inspFilter) inspFilter.addEventListener('change', renderInspectorSegments);

        var diffEl      = document.getElementById('toolSegDiff');
        var slopeTypeEl = document.getElementById('toolSlopeType');
        if (diffEl) {
            diffEl.addEventListener('change', function() {
                if (editorMode) updateEditorDisplay(true);
            });
        }
        if (slopeTypeEl) {
            slopeTypeEl.addEventListener('change', function() {
                if (editorMode) updateEditorDisplay(true);
            });
        }

        // New Sector Form inside Inspector
        var newSecBtn    = document.getElementById('newSector');
        var newSecForm   = document.getElementById('newSectorForm');
        var cancelSecBtn = document.getElementById('cancelNewSectorBtn');
        var saveSecBtn   = document.getElementById('saveNewSectorBtn');

        if (newSecBtn && newSecForm) {
            newSecBtn.addEventListener('click', function() {
                newSecForm.style.display = 'block';
                document.getElementById('newSectorName').focus();
            });
        }
        if (cancelSecBtn && newSecForm) {
            cancelSecBtn.addEventListener('click', function() {
                newSecForm.style.display = 'none';
            });
        }
        if (saveSecBtn) {
            saveSecBtn.addEventListener('click', function() {
                var n = document.getElementById('newSectorName').value.trim();
                var col = document.getElementById('newSectorColor').value || '#3b82f6';
                if (!n) {
                    showToast('Please enter a sector name', 'warning');
                    return;
                }
                postJSON('/map/sector/create', { name: n, color: col }, function(res) {
                    if (res && res.success && res.sector) {
                        SECTORS.push(res.sector);
                        // Also add option to toolbar sector selects
                        ['toolActiveSector', 'toolSegSector'].forEach(function(selId) {
                            var sel = document.getElementById(selId);
                            if (sel) {
                                var opt = document.createElement('option');
                                opt.value = String(res.sector.id);
                                opt.textContent = res.sector.name;
                                sel.appendChild(opt);
                            }
                        });
                        document.getElementById('newSectorName').value = '';
                        newSecForm.style.display = 'none';
                        renderInspectorSectors();
                        showToast('Created sector "' + res.sector.name + '"', 'success');
                    }
                });
            });
        }

        var autoAssignBtn = document.getElementById('autoAssign');
        if (autoAssignBtn) {
            autoAssignBtn.addEventListener('click', function() {
                postJSON('/map/sector/auto-assign', {}, function(r) {
                    if (r && r.success) {
                        if (r.updated) {
                            SEGMENTS.forEach(function(seg) {
                                if (r.updated[seg.id]) seg.sector = r.updated[seg.id];
                            });
                        }
                        renderInspectorSectors();
                        renderInspectorSegments();
                        showToast('Auto-assigned ' + (r.assigned || 0) + ' segments to sectors!', 'success');
                    }
                });
            });
        }

        // Live Rubber-Band Cursor Guide & Magnetic Snapping Preview
        map.on('mousemove', function(e) {
            if (!editorMode) return;
            var isAppending = (editorMode === 'draw' || editorMode === 'sector_boundary' || (editorMode === 'edit' && appendInEditMode));
            if (!isAppending) {
                if (rubberBandLine) {
                    map.removeLayer(rubberBandLine);
                    rubberBandLine = null;
                }
                return;
            }

            var altKey = e.originalEvent && e.originalEvent.altKey;
            var targetLl = getSnappedLatLng(e.latlng, altKey);

            if (editorPoints.length > 0) {
                var lastPt = editorPoints[editorPoints.length - 1];
                var previewPts = [lastPt, [targetLl.lat, targetLl.lng]];
                var accent = getActiveEditorAccentColor();
                if (!rubberBandLine) {
                    rubberBandLine = L.polyline(previewPts, {
                        color: accent,
                        weight: 3,
                        opacity: 0.65,
                        dashArray: '4, 6',
                        interactive: false
                    }).addTo(map);
                } else {
                    rubberBandLine.setLatLngs(previewPts);
                    rubberBandLine.setStyle({ color: accent });
                }
            }
        });

        // Map Click to Add Points (with double-click deduplication guard)
        map.on('click', function(e) {
            if (!editorMode) return;
            var isAppending = (editorMode === 'draw' || editorMode === 'sector_boundary' || (editorMode === 'edit' && appendInEditMode));
            if (!isAppending) {
                selectedVertexIdx = -1;
                updateEditorDisplay(true);
                return;
            }

            var now = Date.now();
            var altKey = e.originalEvent && e.originalEvent.altKey;
            var targetLl = getSnappedLatLng(e.latlng, altKey);

            // Deduplicate rapid double-click so double-clicking to finish doesn't stack 2 identical vertices
            if (now - lastClickTime < 280 && editorPoints.length > 0) {
                var prev = editorPoints[editorPoints.length - 1];
                var dy = prev[0] - targetLl.lat;
                var dx = prev[1] - targetLl.lng;
                if (Math.sqrt(dy * dy + dx * dx) < 1.5) {
                    return;
                }
            }
            lastClickTime = now;

            pushUndoState();
            editorPoints.push([targetLl.lat, targetLl.lng]);
            selectedVertexIdx = editorPoints.length - 1;
            updateEditorDisplay(true);
        });

        map.on('dblclick', function(e) {
            if (!editorMode) return;
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            if (editorMode === 'draw' && editorPoints.length >= 2) {
                saveEditor(false);
            }
        });

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            var tag = e.target && e.target.tagName ? e.target.tagName.toLowerCase() : '';
            if (tag === 'input' || tag === 'select' || tag === 'textarea') {
                if (e.key === 'Enter' && editorMode) {
                    e.preventDefault();
                    saveEditor(e.shiftKey);
                } else if (e.key === 'Escape' && editorMode) {
                    e.preventDefault();
                    cancelEditor(false);
                }
                return;
            }

            if (editorMode) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEditor(e.shiftKey);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEditor(false);
                } else if ((e.key === 'z' || e.key === 'Z') && (e.metaKey || e.ctrlKey || !e.shiftKey)) {
                    e.preventDefault();
                    undoLastAction();
                } else if (e.key === 'r' || e.key === 'R') {
                    e.preventDefault();
                    reverseEditorPoints();
                } else if ((e.key === 'Backspace' || e.key === 'Delete') && selectedVertexIdx !== -1) {
                    e.preventDefault();
                    deleteVertexAt(selectedVertexIdx);
                }
            } else if (IS_ADMIN) {
                if (e.key === 'l' || e.key === 'L') {
                    e.preventDefault();
                    startDraw('lift', false);
                } else if (e.key === 's' || e.key === 'S') {
                    e.preventDefault();
                    startDraw('slope', false);
                } else if (e.key === 'f' || e.key === 'F') {
                    e.preventDefault();
                    map.fitBounds(mapBounds, { animate: true });
                }
            }
        });
    }

    var resortSelect = document.getElementById('resortSelect');
    if (resortSelect) {
        resortSelect.addEventListener('change', function() {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/map/change-map';
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = CSRF_NAME;
            csrfInput.value = CSRF_HASH;
            form.appendChild(csrfInput);
            var mapInput = document.createElement('input');
            mapInput.type = 'hidden';
            mapInput.name = 'map';
            mapInput.value = this.value;
            form.appendChild(mapInput);
            document.body.appendChild(form);
            form.submit();
        });
    }
})();
</script>

<?= $this->endSection() ?>
