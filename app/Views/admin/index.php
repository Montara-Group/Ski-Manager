<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin Panel<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $__seasonLen = max(1, (int) ($season['duration_days'] ?? 135));
    $__seasonPct = min(100, (int) round($gameDay / $__seasonLen * 100));
    $__maintenance = (bool) ($season['maintenance'] ?? 0);
    $__envWritable = is_writable(ROOTPATH . '.env');
    $__tickAge = $lastTick ? time() - strtotime($lastTick['created_at']) : null;
    $__tickStale = $__tickAge === null || $__tickAge > 26 * 3600;
    $__activePct = $totalUsers > 0 ? (int) round($onlineRecent / $totalUsers * 100) : 0;
    $__avgCash = $totalUsers > 0 ? (int) ($totalCash / $totalUsers) : 0;
    $__gb = fn($b) => number_format($b / 1073741824, 1) . ' GB';
    $__navGroups = [
        'Players' => [
            ['/admin/broadcast', 'fa-bullhorn', 'Broadcast'],
            ['/admin/support', 'fa-headset', 'Support', $unreadSupport],
            ['/admin/compare', 'fa-code-compare', 'Compare'],
            ['/admin/suspicious', 'fa-triangle-exclamation', 'Suspicious'],
            ['/admin/export-players', 'fa-download', 'Export'],
        ],
        'Game' => [
            ['/admin/settings', 'fa-gear', 'Settings'],
            ['/admin/economy', 'fa-chart-line', 'Economy'],
            ['/admin/seasons', 'fa-calendar-plus', 'Seasons'],
            ['/admin/features', 'fa-toggle-on', 'Flags'],
            ['/admin/changelogs', 'fa-newspaper', 'Changelogs'],
        ],
        'Logs' => [
            ['/admin/activity', 'fa-clock-rotate-left', 'Activity'],
            ['/admin/audit', 'fa-shield-halved', 'Audit'],
            ['/admin/errors', 'fa-bug', 'Errors'],
        ],
    ];
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8 pb-12">

    <!-- Header -->
    <div class="flex flex-wrap items-end justify-between gap-4 mb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-shield-halved mr-2 text-error"></i>Admin</h1>
                <?php if ($isStaging) : ?>
                    <span class="badge badge-warning font-semibold">Staging</span>
                <?php else : ?>
                    <span class="badge badge-error font-semibold">Production</span>
                <?php endif ?>
                <?php if ($__maintenance) : ?><span class="badge badge-error badge-outline gap-1"><i class="fa-solid fa-wrench text-[10px]"></i>Maintenance</span><?php endif ?>
            </div>
            <div class="text-xs text-base-content/50">Season started <?= esc($season['start_date'] ?? 'N/A') ?> · Server time <?= date('g:ia') ?> (<?= esc(date_default_timezone_get()) ?>)</div>
        </div>
        <div class="w-full sm:w-64">
            <div class="flex justify-between text-xs mb-1"><span class="font-semibold">Day <?= $gameDay ?> of <?= $__seasonLen ?></span><?php if ($gameDay > $__seasonLen) : ?><a href="/admin/seasons" class="link link-hover text-warning font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i><?= $gameDay - $__seasonLen ?> days past season end</a><?php else : ?><span class="text-base-content/50"><?= $__seasonPct ?>%</span><?php endif ?></div>
            <progress class="progress <?= $gameDay > $__seasonLen ? 'progress-warning' : 'progress-primary' ?> w-full" value="<?= $__seasonPct ?>" max="100"></progress>
        </div>
    </div>

    <?php if (session('success')) : ?><div role="alert" class="alert alert-success mb-4"><i class="fa-solid fa-circle-check"></i><span><?= esc(session('success')) ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div role="alert" class="alert alert-error mb-4"><i class="fa-solid fa-circle-exclamation"></i><span><?= esc(session('error')) ?></span></div><?php endif ?>

    <!-- Navigation -->
    <nav class="card bg-base-100 shadow-sm mb-4" aria-label="Admin sections"><div class="card-body p-3">
        <div class="flex flex-wrap gap-x-6 gap-y-3">
            <?php foreach ($__navGroups as $__group => $__links) : ?>
                <div>
                    <div class="text-[10px] uppercase tracking-wide text-base-content/40 font-semibold mb-1"><?= $__group ?></div>
                    <div class="flex flex-wrap gap-1">
                        <?php foreach ($__links as $__l) : ?>
                            <a href="<?= $__l[0] ?>" class="btn btn-ghost btn-sm gap-1.5"><i class="fa-solid <?= $__l[1] ?> text-base-content/50"></i><?= $__l[2] ?><?php if (!empty($__l[3])) : ?><span class="badge badge-error badge-xs"><?= (int) $__l[3] ?></span><?php endif ?></a>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </div></nav>

    <!-- Game controls -->
    <div class="card bg-base-100 shadow-sm mb-6 <?= $__maintenance ? 'ring-2 ring-error' : '' ?>"><div class="card-body p-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 text-sm mr-auto">
                <span class="inline-block w-2 h-2 rounded-full <?= $__tickStale ? 'bg-error' : 'bg-success' ?>"></span>
                <span class="text-base-content/60">Last tick:</span>
                <?php if ($lastTick) : ?>
                    <span class="font-semibold <?= $__tickStale ? 'text-error' : '' ?>" title="<?= esc($lastTick['created_at']) ?>"><?= timeAgo($lastTick['created_at']) ?></span>
                    <span class="text-xs text-base-content/40">(wrote day <?= (int) $lastTick['game_day'] ?>)</span>
                <?php else : ?>
                    <span class="font-semibold text-error">never</span>
                <?php endif ?>
            </div>
            <form action="/admin/maintenance" method="post" onsubmit="return confirm('<?= $__maintenance ? 'Turn maintenance OFF and reopen the site to players?' : 'Turn maintenance ON? Players will see a 503 page.' ?>')"><?= csrf_field() ?>
                <button class="btn btn-sm <?= $__maintenance ? 'btn-error' : 'btn-outline' ?> gap-1"><i class="fa-solid fa-wrench"></i>Maintenance: <?= $__maintenance ? 'ON' : 'off' ?></button>
            </form>
            <form action="/admin/toggle-env" method="post"><?= csrf_field() ?>
                <button class="btn btn-sm <?= ENVIRONMENT === 'development' ? 'btn-warning' : 'btn-outline' ?> gap-1" <?= $__envWritable ? '' : 'disabled' ?> title="<?= $__envWritable ? 'Toggle CI_ENVIRONMENT between development and production' : '.env is read-only on the server (edit it directly or chmod 664 .env)' ?>">
                    <i class="fa-solid fa-code"></i><?= ENVIRONMENT === 'development' ? 'DEV mode' : 'PROD mode' ?>
                    <?php if (!$__envWritable) : ?><i class="fa-solid fa-lock text-[10px] opacity-70"></i><?php endif ?>
                </button>
            </form>
            <form action="/admin/trigger-tick" method="post" onsubmit="return confirm('Run the game tick on <?= $isStaging ? 'STAGING' : 'PRODUCTION' ?> now? This advances every player by a day.')"><?= csrf_field() ?>
                <button class="btn btn-error btn-sm gap-1"><i class="fa-solid fa-play"></i>Run tick</button>
            </form>
        </div>
    </div></div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <?php
            $__kpis = [
                ['fa-users', 'Players', number_format($totalUsers), '+' . $newUsers7d . ' this week' . ($bannedUsers ? ' · ' . $bannedUsers . ' banned' : ''), ''],
                ['fa-signal', 'Active 24h', number_format($onlineRecent), $__activePct . '% of players', 'text-success'],
                ['fa-coins', 'Total cash', currency($totalCash), currency($__avgCash) . ' avg per player', 'text-success'],
                ['fa-file-invoice-dollar', 'Active loans', number_format($totalLoans), '', 'text-warning'],
                ['fa-user-group', 'Staff', number_format($totalStaff), '', ''],
                ['fa-hotel', 'Buildings', number_format($totalBuildings), '', ''],
                ['fa-person-skiing', 'Slopes & lifts', number_format($totalItems), '', ''],
                ['fa-square-parking', 'Parking & parks', number_format($totalParking + $totalParks), $totalParking . ' lots · ' . $totalParks . ' parks', ''],
            ];
        ?>
        <?php foreach ($__kpis as [$__icon, $__label, $__value, $__sub, $__color]) : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 gap-0.5">
                <div class="text-xs text-base-content/50"><i class="fa-solid <?= $__icon ?> fa-fw mr-1"></i><?= $__label ?></div>
                <div class="text-xl sm:text-2xl font-bold whitespace-nowrap <?= $__color ?>"><?= $__value ?></div>
                <?php if ($__sub) : ?><div class="text-[11px] text-base-content/40"><?= esc($__sub) ?></div><?php endif ?>
            </div></div>
        <?php endforeach ?>
    </div>

    <!-- Players + activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h2 class="text-lg font-bold"><i class="fa-solid fa-users mr-1"></i>Players <span class="text-xs font-normal text-base-content/50">newest <?= count($users) ?> of <?= number_format($totalUsers) ?></span></h2>
                <div class="flex items-center gap-2">
                    <div class="join" role="group" aria-label="Filter players">
                        <button type="button" class="btn btn-xs join-item btn-active" data-filter="all">All</button>
                        <button type="button" class="btn btn-xs join-item" data-filter="online">Online</button>
                        <button type="button" class="btn btn-xs join-item" data-filter="banned">Banned</button>
                    </div>
                    <search>
                        <input type="search" id="playerSearch" placeholder="Name or ID…" aria-label="Search players" class="input input-bordered input-xs w-36">
                    </search>
                </div>
            </div>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-auto max-h-[600px]">
                <table class="table table-sm table-pin-rows" id="playerTable">
                    <thead class="bg-base-100 z-10"><tr>
                        <th data-sort="num" class="cursor-pointer select-none">ID</th>
                        <th data-sort="text" class="cursor-pointer select-none">Username</th>
                        <th data-sort="num" class="cursor-pointer select-none text-right">Cash</th>
                        <th data-sort="num" class="cursor-pointer select-none text-right" title="Staff">Staff</th>
                        <th data-sort="num" class="cursor-pointer select-none text-right" title="Buildings">Bldg</th>
                        <th data-sort="num" class="cursor-pointer select-none text-right" title="Slopes and lifts">Items</th>
                        <th data-sort="num" class="cursor-pointer select-none">Joined</th>
                        <th data-sort="num" class="cursor-pointer select-none">Last active</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($users as $u) : ?>
                        <?php
                            $__banned = isset($u['active']) && !$u['active'];
                            $__lastTs = !empty($u['last_active']) ? strtotime($u['last_active']) : 0;
                            $__online = $__lastTs > strtotime('-15 minutes');
                        ?>
                        <tr class="player-row hover:bg-base-200 <?= $__banned ? 'opacity-50' : '' ?>" data-online="<?= $__online ? 1 : 0 ?>" data-banned="<?= $__banned ? 1 : 0 ?>">
                            <td data-v="<?= (int) $u['id'] ?>" class="text-base-content/50"><?= (int) $u['id'] ?></td>
                            <td data-v="<?= esc(strtolower($u['username'] ?? '')) ?>" class="font-semibold whitespace-nowrap">
                                <a href="/admin/user/<?= (int) $u['id'] ?>" class="link link-hover"><?= esc($u['username']) ?></a>
                                <?php if ($__banned) : ?><span class="badge badge-error badge-xs ml-1">Banned</span><?php endif ?>
                            </td>
                            <td data-v="<?= $u['cash'] ?>" class="font-mono text-xs text-right whitespace-nowrap <?= $u['cash'] < 0 ? 'text-error' : '' ?>"><?= currency($u['cash']) ?></td>
                            <td data-v="<?= $u['staff_count'] ?>" class="text-right"><?= $u['staff_count'] ?></td>
                            <td data-v="<?= $u['building_count'] ?>" class="text-right"><?= $u['building_count'] ?></td>
                            <td data-v="<?= $u['item_count'] ?>" class="text-right"><?= $u['item_count'] ?></td>
                            <td data-v="<?= strtotime($u['created_at']) ?>" class="text-xs text-base-content/50 whitespace-nowrap" title="<?= esc($u['created_at']) ?>"><?= date('M j', strtotime($u['created_at'])) ?></td>
                            <td data-v="<?= $__lastTs ?>" class="text-xs whitespace-nowrap <?= $__lastTs > strtotime('-1 hour') ? 'text-success font-semibold' : 'text-base-content/50' ?>" title="<?= $u['last_activity'] ? esc($u['last_activity']['message']) : '' ?>">
                                <?php if ($__online) : ?><span class="inline-block w-2 h-2 rounded-full bg-success mr-1" title="Online now"></span><?php endif ?>
                                <?= $__lastTs ? timeAgo($u['last_active']) : 'Never' ?>
                            </td>
                            <td>
                                <div class="flex gap-0.5 justify-end">
                                    <a href="/admin/user/<?= (int) $u['id'] ?>" class="btn btn-ghost btn-xs" aria-label="Edit <?= esc($u['username']) ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <?php if ((int) $u['id'] !== 1) : ?>
                                        <?php if ($__banned) : ?>
                                            <form action="/admin/unban/<?= (int) $u['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-success" aria-label="Unban <?= esc($u['username']) ?>" title="Unban"><i class="fa-solid fa-unlock"></i></button></form>
                                        <?php else : ?>
                                            <form action="/admin/ban/<?= (int) $u['id'] ?>" method="post" onsubmit="return confirm('Ban <?= esc($u['username'], 'js') ?>?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-warning" aria-label="Ban <?= esc($u['username']) ?>" title="Ban"><i class="fa-solid fa-ban"></i></button></form>
                                        <?php endif ?>
                                        <form action="/admin/delete/<?= (int) $u['id'] ?>" method="post" onsubmit="return confirm('Permanently delete <?= esc($u['username'], 'js') ?> and all their resort data? This cannot be undone.')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error" aria-label="Delete <?= esc($u['username']) ?>" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <tr id="noResults" class="hidden"><td colspan="9" class="text-center text-base-content/40 py-6">No players match.</td></tr>
                    </tbody>
                </table>
            </div></div></div>
        </div>

        <div class="min-w-0">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Activity</h2>
                <a href="/admin/activity" class="link link-hover text-xs text-base-content/50">View all</a>
            </div>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0">
                <div class="divide-y divide-base-300 max-h-[600px] overflow-y-auto">
                <?php if (empty($recentLogs)) : ?>
                    <div class="p-6 text-center text-xs text-base-content/40">No activity yet.</div>
                <?php endif ?>
                <?php foreach ($recentLogs as $log) : ?>
                    <div class="flex items-start gap-2 p-2 text-xs">
                        <i class="<?= esc($log['icon'], 'attr') ?> text-base-content/50 w-4 text-center mt-0.5"></i>
                        <div class="flex-1 min-w-0">
                            <div class="truncate" title="<?= esc($log['message']) ?>"><span class="font-semibold text-primary"><?= esc($log['username']) ?></span> <?= esc($log['message']) ?></div>
                            <div class="text-[10px] text-base-content/40">Day <?= (int) $log['game_day'] ?> · <?= timeAgo($log['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach ?>
                </div>
            </div></div>
        </div>
    </div>

    <!-- Tools -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-1"><i class="fa-solid fa-cloud-sun mr-1 text-info"></i>Weather</h2>
            <?php if ($weather) : ?>
                <?php
                    $__cond = $weather['condition_name'] ?? '';
                    $__wicon = match(true) {
                        stripos($__cond, 'blizzard') !== false => 'fa-wind text-info',
                        stripos($__cond, 'snow') !== false => 'fa-snowflake text-info',
                        stripos($__cond, 'rain') !== false => 'fa-cloud-rain text-info',
                        stripos($__cond, 'fog') !== false => 'fa-smog text-base-content/50',
                        stripos($__cond, 'cloud') !== false => 'fa-cloud text-base-content/50',
                        stripos($__cond, 'sun') !== false => 'fa-sun text-warning',
                        default => 'fa-cloud-sun text-warning',
                    };
                ?>
                <div class="flex items-center gap-3 rounded-box bg-base-200 p-3 mb-3">
                    <i class="fa-solid <?= $__wicon ?> text-2xl"></i>
                    <div class="flex-1">
                        <div class="font-semibold text-sm"><?= esc($__cond) ?></div>
                        <div class="text-[11px] text-base-content/50">Now · <?= temp($weather['temp']) ?> · <?= speed($weather['wind']) ?> · <?= snow($weather['snow_base']) ?> base</div>
                    </div>
                </div>
            <?php endif ?>
            <form action="/admin/set-weather" method="post"><?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="col-span-2"><label class="label text-xs py-0" for="wCond">Condition</label>
                    <select name="condition" id="wCond" class="select select-bordered select-xs w-full">
                        <?php foreach (['Sunny', 'Partly Cloudy', 'Cloudy', 'Light Snow', 'Heavy Snow', 'Blizzard', 'Freezing Rain'] as $__c) : ?>
                            <option value="<?= $__c ?>" <?= ($weather['condition_name'] ?? 'Light Snow') === $__c ? 'selected' : '' ?>><?= $__c ?></option>
                        <?php endforeach ?>
                    </select></div>
                    <div><label class="label text-xs py-0" for="wTemp">Temp (°C)</label>
                    <input type="number" name="temp" id="wTemp" value="<?= (int) ($weather['temp'] ?? -5) ?>" class="input input-bordered input-xs w-full" min="-30" max="30"></div>
                    <div><label class="label text-xs py-0" for="wWind">Wind (km/h)</label>
                    <input type="number" name="wind" id="wWind" value="<?= (int) ($weather['wind'] ?? 15) ?>" class="input input-bordered input-xs w-full" min="0" max="120"></div>
                    <div><label class="label text-xs py-0" for="wSnow">Snowfall (cm)</label>
                    <input type="number" name="snowfall" id="wSnow" value="<?= (int) ($weather['snowfall'] ?? 0) ?>" class="input input-bordered input-xs w-full" min="0" max="50"></div>
                    <div><label class="label text-xs py-0" for="wBase">Snow base (cm)</label>
                    <input type="number" name="snow_base" id="wBase" value="<?= (int) ($weather['snow_base'] ?? 50) ?>" class="input input-bordered input-xs w-full" min="0" max="300"></div>
                </div>
                <button class="btn btn-info btn-sm w-full gap-1"><i class="fa-solid fa-cloud-sun"></i>Apply weather</button>
            </form>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-bolt mr-1 text-warning"></i>Bulk actions <span class="badge badge-warning badge-xs">All players</span></h2>
            <div class="space-y-3">
                <form action="/admin/add-cash-all" method="post" onsubmit="return confirm('Give ' + Number(this.amount.value).toLocaleString() + ' cash to ALL <?= (int) $totalUsers ?> players? This affects the economy and leaderboard.')"><?= csrf_field() ?>
                    <label class="label text-xs py-0" for="cashAll"><span><i class="fa-solid fa-coins text-success mr-1"></i>Give cash to everyone</span></label>
                    <div class="join w-full">
                        <input type="number" name="amount" id="cashAll" value="10000" min="1" max="10000000" class="input input-bordered input-xs join-item flex-1" placeholder="Amount">
                        <button class="btn btn-success btn-xs join-item gap-1"><i class="fa-solid fa-paper-plane"></i>Give</button>
                    </div>
                </form>
                <form action="/admin/add-genepis-all" method="post" onsubmit="return confirm('Give ' + Number(this.amount.value).toLocaleString() + ' Genepis to ALL <?= (int) $totalUsers ?> players?')"><?= csrf_field() ?>
                    <label class="label text-xs py-0" for="genAll"><span><i class="fa-solid fa-seedling text-info mr-1"></i>Give Genepis to everyone</span></label>
                    <div class="join w-full">
                        <input type="number" name="amount" id="genAll" value="100" min="1" max="100000" class="input input-bordered input-xs join-item flex-1" placeholder="Amount">
                        <button class="btn btn-info btn-xs join-item gap-1"><i class="fa-solid fa-paper-plane"></i>Give</button>
                    </div>
                </form>
                <div class="divider my-1 text-[10px] text-base-content/40">Single player</div>
                <form action="/admin/grant-achievement" method="post" onsubmit="return confirm('Grant achievement #' + this.achievement_id.value + ' to user #' + this.user_id.value + '?')"><?= csrf_field() ?>
                    <label class="label text-xs py-0"><span><i class="fa-solid fa-trophy text-warning mr-1"></i>Grant achievement</span></label>
                    <div class="join w-full">
                        <input type="number" name="user_id" min="1" class="input input-bordered input-xs join-item flex-1 min-w-0" placeholder="User ID" aria-label="User ID" required>
                        <input type="number" name="achievement_id" min="1" class="input input-bordered input-xs join-item flex-1 min-w-0" placeholder="Ach. ID" aria-label="Achievement ID" required>
                        <button class="btn btn-warning btn-xs join-item gap-1"><i class="fa-solid fa-check"></i>Grant</button>
                    </div>
                </form>
            </div>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-server mr-1 text-primary"></i>Server health</h2>
            <div class="mb-3">
                <div class="flex justify-between text-xs mb-1"><span class="text-base-content/50"><i class="fa-solid fa-hard-drive mr-1"></i>Disk</span><span class="font-mono"><?= $__gb($server['diskUsed']) ?> / <?= $__gb($server['diskTotal']) ?></span></div>
                <progress class="progress <?= $server['diskPct'] > 85 ? 'progress-error' : ($server['diskPct'] > 70 ? 'progress-warning' : 'progress-success') ?> w-full" value="<?= $server['diskPct'] ?>" max="100"></progress>
            </div>
            <div class="mb-3">
                <div class="flex justify-between text-xs mb-1"><span class="text-base-content/50"><i class="fa-solid fa-gauge-high mr-1"></i>Load (1/5/15m)</span><span class="font-mono <?= $server['load'][0] > $server['cores'] ? 'text-error' : '' ?>"><?= implode(' / ', array_map(fn($l) => number_format($l, 2), $server['load'])) ?></span></div>
                <div class="text-[10px] text-base-content/40"><?= $server['cores'] ?> CPU core<?= $server['cores'] === 1 ? '' : 's' ?></div>
            </div>
            <div class="space-y-1 text-xs border-t border-base-300 pt-2">
                <div class="flex justify-between"><span class="text-base-content/50">PHP / CI4</span><span class="font-mono"><?= PHP_VERSION ?> / <?= \CodeIgniter\CodeIgniter::CI_VERSION ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Memory (peak/limit)</span><span class="font-mono"><?= $server['memPeak'] ?>M / <?= esc($server['memLimit']) ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Database</span><span class="font-mono">MySQL <?= esc($server['dbVersion']) ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">DB size</span><span class="font-mono"><?= $server['dbSize'] ?> MB</span></div>
            </div>
            <a href="/admin/errors" class="btn btn-outline btn-xs w-full mt-3 gap-1"><i class="fa-solid fa-bug"></i>View error log</a>
        </div></div>
    </div>
</div>
<script>
(function () {
    var table = document.getElementById('playerTable');
    var tbody = table.tBodies[0];
    var search = document.getElementById('playerSearch');
    var empty = document.getElementById('noResults');
    var filter = 'all';

    function apply() {
        var q = search.value.trim().toLowerCase();
        var shown = 0;
        tbody.querySelectorAll('tr.player-row').forEach(function (r) {
            var id = r.cells[0].textContent.trim();
            var name = r.cells[1].dataset.v;
            var matchQ = !q || name.includes(q) || id === q.replace('#', '');
            var matchF = filter === 'all' || r.dataset[filter] === '1';
            var show = matchQ && matchF;
            r.style.display = show ? '' : 'none';
            if (show) shown++;
        });
        empty.classList.toggle('hidden', shown > 0);
    }

    search.addEventListener('input', apply);
    document.querySelectorAll('[data-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            filter = btn.dataset.filter;
            document.querySelectorAll('[data-filter]').forEach(function (b) { b.classList.toggle('btn-active', b === btn); });
            apply();
        });
    });

    table.querySelectorAll('th[data-sort]').forEach(function (th, col) {
        th.addEventListener('click', function () {
            var dir = th.dataset.dir === 'asc' ? 'desc' : 'asc';
            table.querySelectorAll('th[data-sort]').forEach(function (h) { delete h.dataset.dir; h.removeAttribute('aria-sort'); });
            th.dataset.dir = dir;
            th.setAttribute('aria-sort', dir === 'asc' ? 'ascending' : 'descending');
            var numeric = th.dataset.sort === 'num';
            var rows = Array.from(tbody.querySelectorAll('tr.player-row'));
            rows.sort(function (a, b) {
                var x = a.cells[col].dataset.v, y = b.cells[col].dataset.v;
                var cmp = numeric ? (parseFloat(x) || 0) - (parseFloat(y) || 0) : x.localeCompare(y);
                return dir === 'asc' ? cmp : -cmp;
            });
            rows.forEach(function (r) { tbody.insertBefore(r, empty); });
        });
    });
})();
</script>
<?= $this->endSection() ?>
