<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ski Manager - Free Online Ski Resort Tycoon Game<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
    $db = db_connect();
    $playerCount = $db->table('users')->where('id !=', 1)->countAllResults(false);
    $totalSlopes = $db->table('player_items')->where('item_type', 'slope')->countAllResults(false);
    $topPlayer = $db->query("SELECT u.username, pf.cash FROM player_finances pf JOIN users u ON u.id = pf.user_id WHERE u.id != 1 ORDER BY pf.cash DESC LIMIT 1")->getRowArray();
    $recentPlayer = $db->table('users')->where('id !=', 1)->orderBy('created_at', 'DESC')->limit(1)->get()->getRowArray();
    $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
    $weather = getCurrentWeather();
    $playersLabel = $playerCount >= 10 ? number_format($playerCount) . '+ active managers' : 'Be one of the first managers';

    $currentSeasonNum = (int) getSeasonNumber();
    $seasonDay = (int) getSeasonDay();
    $seasonLen = (int) getSeasonLength();
    $seasonProgressPct = min(100, max(0, (int) round(($seasonDay / max(1, $seasonLen)) * 100)));
    $daysRemaining = max(0, $seasonLen - $seasonDay);
?>

<?php
    $__wCond   = $weather ? ($weather['condition_name'] ?? $weather['condition'] ?? '') : '';
    $__wFresh  = $weather ? (int) ($weather['snowfall'] ?? 0) : 0;
    $__seasons = [
        1 => ['title' => 'Park City base', 'text' => 'Beginner and intermediate terrain. Build the first lifts, set up ski patrol and keep the books balanced.', 'items' => ['Green and blue runs', 'Snowmaking and grooming', 'Staying solvent']],
        2 => ['title' => 'Advanced peaks', 'text' => 'Steep bowls and high ridges open up. Your cash and everything you built carries over.', 'items' => ['Black and double-black terrain', 'Express gondolas', 'Storm operations']],
        3 => ['title' => 'Beyond Park City', 'text' => 'Backcountry, terrain parks and tournaments between resorts, with players voting on the next mountain.', 'items' => ['Backcountry and parks', 'Player-chosen resorts', 'Inter-resort tournaments']],
    ];
?>
<style>
.home-hero { position: relative; isolation: isolate; overflow: hidden; color: #fff; background: #141b24; }
.home-hero::before {
    content: ''; position: absolute; inset: 0; z-index: -2;
    background-image: url('/img/ParkCity_med.jpg'), url('/img/ParkCity_low.jpg');
    background-size: cover; background-position: center 35%; opacity: 0.75;
}
.home-hero::after {
    content: ''; position: absolute; inset: 0; z-index: -1;
    background: linear-gradient(90deg, rgba(10,14,20,0.92) 0%, rgba(10,14,20,0.78) 45%, rgba(10,14,20,0.35) 100%);
}
@media (max-width: 1023px) { .home-hero::after { background: rgba(10,14,20,0.82); } }
.home-hero h1 { font-family: var(--sign); font-weight: 700; font-size: clamp(2.75rem, 6vw, 4.5rem); line-height: 0.98; letter-spacing: 0.005em; margin: 0; }
.home-hero h1 span { color: #7fb4ff; }
.home-hero .lede { font-size: 1.0625rem; line-height: 1.6; color: rgba(255,255,255,0.82); max-width: 34rem; }
.home-hero .facts { display: flex; flex-wrap: wrap; gap: 0.35rem 1.25rem; font-size: 0.8125rem; color: rgba(255,255,255,0.7); }
.home-hero .facts b { color: #fff; font-weight: 600; }
.home-hero .ghost { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.3); color: #fff; }
.home-hero .ghost:hover { background: rgba(255,255,255,0.22); color: #fff; }

/* Snow report board */
.report { background: #f7f5f0; color: #1d232b; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.35); overflow: hidden; }
.report-head { display: flex; align-items: baseline; justify-content: space-between; padding: 0.85rem 1.1rem 0.7rem; background: #1d232b; color: #f7f5f0; }
.report-head h2 { font-family: var(--sign); font-size: 1.5rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; line-height: 1; margin: 0; }
.report-head span { font-size: 0.75rem; opacity: 0.7; }
.report-big { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid rgba(0,0,0,0.1); }
.report-big > div { padding: 0.9rem 1.1rem; }
.report-big > div + div { border-left: 1px solid rgba(0,0,0,0.1); }
.report-label { font-size: 0.75rem; color: #5b6470; }
.report-num { font-family: var(--sign); font-size: 2.5rem; font-weight: 600; line-height: 1; margin-top: 0.15rem; font-variant-numeric: tabular-nums; }
.report-num small { font-size: 1rem; font-weight: 500; color: #5b6470; margin-left: 4px; }
.report-rows > div { display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 1.1rem; font-size: 0.875rem; border-top: 1px solid rgba(0,0,0,0.08); }
.report-rows > div:first-child { border-top: 0; }
.report-rows dt { color: #5b6470; }
.report-rows dd { font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.home-section { padding: 4.5rem 0; }
.home-section h2.sign { font-family: var(--sign); font-size: 2.25rem; font-weight: 700; line-height: 1.05; margin: 0; }
.home-kicker { font-size: 0.8125rem; font-weight: 600; color: var(--color-primary); margin-bottom: 0.35rem; }
.pillar h3 { font-family: var(--sign); font-size: 1.5rem; font-weight: 600; line-height: 1.1; margin: 0.75rem 0 0.5rem; }
.pillar p { font-size: 0.9375rem; line-height: 1.6; color: color-mix(in oklab, var(--color-base-content) 75%, transparent); }
.pillar-mark { display: flex; align-items: center; gap: 0.4rem; height: 1.25rem; }
.tsign { display: inline-block; width: 14px; height: 14px; }
.tsign.green { border-radius: 50%; background: #23a55a; }
.tsign.blue { background: #2f6fd6; }
.tsign.black { width: 12px; height: 12px; background: var(--color-base-content); transform: rotate(45deg); margin: 0 2px; }

.resort-list { display: flex; flex-wrap: wrap; gap: 0.25rem 1rem; font-size: 0.9375rem; }
.resort-list li { display: flex; align-items: center; gap: 0.45rem; }
.resort-list li::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: color-mix(in oklab, var(--color-base-content) 35%, transparent); }
.map-frame { display: block; border-radius: 8px; overflow: hidden; border: 1px solid var(--color-base-300); box-shadow: 0 8px 24px rgba(0,0,0,0.18); }
.map-frame img { display: block; width: 100%; height: auto; transition: transform .4s ease; }
.map-frame:hover img { transform: scale(1.02); }

/* Season timeline drawn like a lift line */
.seasons { position: relative; display: grid; grid-template-columns: 1fr; gap: 2rem; }
@media (min-width: 768px) {
    .seasons { grid-template-columns: repeat(3, 1fr); gap: 2.5rem; }
    .seasons::before { content: ''; position: absolute; left: 0; right: 0; top: 7px; height: 2px; background: var(--color-base-300); }
}
.season { position: relative; }
.season-dot { position: relative; width: 16px; height: 16px; border-radius: 50%; background: var(--color-base-100); border: 2px solid var(--color-base-300); margin-bottom: 1rem; }
.season.is-done .season-dot { background: var(--color-base-content); border-color: var(--color-base-content); opacity: 0.45; }
.season.is-now .season-dot { background: var(--color-primary); border-color: var(--color-primary); box-shadow: 0 0 0 5px color-mix(in oklab, var(--color-primary) 22%, transparent); }
.season-tag { font-size: 0.75rem; font-weight: 600; color: color-mix(in oklab, var(--color-base-content) 55%, transparent); }
.season.is-now .season-tag { color: var(--color-primary); }
.season h3 { font-family: var(--sign); font-size: 1.625rem; font-weight: 600; line-height: 1.1; margin: 0.15rem 0 0.45rem; }
.season p { font-size: 0.9rem; line-height: 1.55; color: color-mix(in oklab, var(--color-base-content) 72%, transparent); }
.season ul { margin-top: 0.75rem; font-size: 0.875rem; }
.season li { padding: 0.3rem 0; border-top: 1px solid var(--color-base-300); }

.feature-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem 2rem; }
@media (min-width: 1024px) { .feature-list { grid-template-columns: repeat(4, 1fr); } }
.feature-list div { font-size: 0.875rem; color: color-mix(in oklab, var(--color-base-content) 68%, transparent); }
.feature-list b { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9375rem; color: var(--color-base-content); font-weight: 600; margin-bottom: 0.15rem; }
.feature-list b i { color: var(--color-primary); font-size: 0.875rem; width: 1rem; text-align: center; }

/* Utilities not in the compiled Tailwind build */
.align-middle { vertical-align: middle; }
.gap-10 { gap: 2.5rem; }
.mb-12 { margin-bottom: 3rem; }
.py-14 { padding-top: 3.5rem; padding-bottom: 3.5rem; }
@media (min-width: 768px) { .md\:py-20 { padding-top: 5rem; padding-bottom: 5rem; } }
.text-base-content\/55 { color: color-mix(in oklab, var(--color-base-content) 55%, transparent); }
.text-base-content\/75 { color: color-mix(in oklab, var(--color-base-content) 75%, transparent); }
.underline-offset-4 { text-underline-offset: 4px; }

.home-cta { background: #1d232b; color: #f7f5f0; }
.home-cta h2 { font-family: var(--sign); font-size: clamp(2rem, 4vw, 2.75rem); font-weight: 700; line-height: 1.05; margin: 0; }
</style>

<!-- Hero -->
<section class="home-hero">
    <div class="max-w-6xl mx-auto px-4 py-14 md:py-20 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
        <div class="lg:col-span-7 space-y-6">
            <div class="text-sm" style="color:rgba(255,255,255,0.75)">
                <span class="inline-block w-2 h-2 rounded-full bg-success mr-1.5 align-middle"></span>Season <?= $currentSeasonNum ?> is on · day <?= $seasonDay ?> of <?= $seasonLen ?>
            </div>
            <h1>Design slopes.<br>Spin chairlifts.<br><span>Rule the mountain.</span></h1>
            <p class="lede">Start with an empty mountain and up to <?= currency(1000000) ?> in capital. Build lifts, lay out runs, hire staff, make snow and keep the resort profitable.</p>
            <div class="flex flex-wrap items-center gap-3">
                <?php if (!auth()->loggedIn()) : ?>
                <a href="/register" class="btn btn-primary px-6">Play free</a>
                <a href="/demo" class="btn ghost">Try the demo mountain</a>
                <a href="/login" class="text-sm underline underline-offset-4 ml-1" style="color:rgba(255,255,255,0.8)">Sign in</a>
                <?php else : ?>
                <a href="/dashboard" class="btn btn-primary px-6">Go to your resort</a>
                <a href="/map" class="btn ghost">Open trail map</a>
                <?php endif ?>
            </div>
            <div class="facts">
                <span><?= $playersLabel ?></span>
                <span>Plays in your browser</span>
                <span>Nothing to download</span>
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="report">
                <div class="report-head">
                    <h2>Snow report</h2>
                    <span>Park City · today</span>
                </div>
                <div class="report-big">
                    <div>
                        <div class="report-label">Summit</div>
                        <div class="report-num"><?= $weather ? esc($weather['temp_formatted']) : '–' ?></div>
                        <div class="text-xs mt-1" style="color:#5b6470"><?= esc($__wCond ?: '—') ?></div>
                    </div>
                    <div>
                        <div class="report-label">Base depth</div>
                        <div class="report-num"><?= isset($weather['snow_base']) ? snow($weather['snow_base']) : '–' ?></div>
                        <div class="text-xs mt-1" style="color:#5b6470"><?= $__wFresh > 0 ? snow($__wFresh) . ' new snow' : 'No new snow' ?></div>
                    </div>
                </div>
                <dl class="report-rows">
                    <div><dt>Season</dt><dd><?= $currentSeasonNum ?>, day <?= $seasonDay ?> of <?= $seasonLen ?></dd></div>
                    <div><dt>Runs built by players</dt><dd><?= number_format($totalSlopes) ?></dd></div>
                    <?php if ($topPlayer) : ?>
                    <div><dt>Richest resort</dt><dd><?= esc($topPlayer['username']) ?> · <?= currency((int) $topPlayer['cash']) ?></dd></div>
                    <?php endif ?>
                </dl>
            </div>
        </div>
    </div>
</section>

<!-- What you manage -->
<section class="home-section bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <div class="home-kicker">What you're running</div>
        <h2 class="sign mb-10">Three things keep a resort open.</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <div class="pillar">
                <div class="pillar-mark" aria-hidden="true"><i class="tsign green"></i><i class="tsign blue"></i><i class="tsign black"></i></div>
                <h3>Runs and lifts</h3>
                <p>Draw runs along the real fall lines of the mountain, grade them from green to double black, and choose lifts that keep the lines short. Players have built <b class="text-base-content"><?= number_format($totalSlopes) ?></b> runs so far.</p>
            </div>
            <div class="pillar">
                <div class="pillar-mark" aria-hidden="true"><i class="fa-solid fa-ticket text-lg text-warning"></i></div>
                <h3>Money</h3>
                <p>Set ticket prices, build lodges, restaurants, rentals and parking, borrow when you have to, and try not to run dry before the season ends.</p>
            </div>
            <div class="pillar">
                <div class="pillar-mark" aria-hidden="true"><i class="fa-solid fa-snowflake text-lg text-info"></i></div>
                <h3>Snow</h3>
                <p>Watch the forecast, fire up the snow guns when it's cold enough, and send groomers out overnight so the runs are in shape by morning.</p>
            </div>
        </div>
    </div>
</section>

<!-- Real maps -->
<section class="home-section bg-base-200 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
        <div class="lg:col-span-5 space-y-4">
            <div class="home-kicker">Real mountains</div>
            <h2 class="sign">Built on real trail maps.</h2>
            <p class="text-base-content/75 leading-relaxed">You build on the actual terrain of well-known resorts, following their ridges, bowls and base areas.</p>
            <ul class="resort-list pt-1">
                <li>Park City</li><li>Deer Valley</li><li>Vail</li><li>Aspen</li><li>Big Sky</li><li>Palisades Tahoe</li>
            </ul>
            <p class="text-xs text-base-content/55 pt-2">Maps by <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" class="underline">Mapsynergy</a></p>
        </div>
        <div class="lg:col-span-7">
            <a href="<?= auth()->loggedIn() ? '/map' : '/register' ?>" class="map-frame">
                <img src="/img/ParkCity_low.jpg" alt="Park City trail map" width="600" height="340" loading="lazy" decoding="async">
            </a>
        </div>
    </div>
</section>

<!-- Seasons -->
<section class="home-section bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <div class="home-kicker">Seasons</div>
        <h2 class="sign mb-12">The mountain opens up a sector at a time.</h2>
        <div class="seasons">
            <?php foreach ($__seasons as $__n => $__s) :
                $__isNow  = $__n === 3 ? $currentSeasonNum >= 3 : $__n === $currentSeasonNum;
                $__isDone = $__n < $currentSeasonNum && !$__isNow;
                $__state  = $__isNow ? 'is-now' : ($__isDone ? 'is-done' : '');
            ?>
            <div class="season <?= $__state ?>">
                <div class="season-dot"></div>
                <div class="season-tag">Season <?= $__n ?><?= $__n === 3 ? '+' : '' ?> · <?= $__isNow ? 'Now playing' : ($__isDone ? 'Finished' : 'Coming up') ?></div>
                <h3><?= esc($__s['title']) ?></h3>
                <p><?= esc($__s['text']) ?></p>
                <?php if ($__isNow) : ?>
                <div class="mt-3">
                    <progress class="progress progress-primary w-full h-1.5" value="<?= $seasonDay ?>" max="<?= max(1, $seasonLen) ?>"></progress>
                    <div class="flex justify-between text-xs text-base-content/60 mt-1"><span>Day <?= $seasonDay ?> of <?= $seasonLen ?></span><span><?= $daysRemaining ?> days left</span></div>
                </div>
                <?php endif ?>
                <ul>
                    <?php foreach ($__s['items'] as $__it) : ?><li><?= esc($__it) ?></li><?php endforeach ?>
                </ul>
                <?php if ($__n === 3) : ?>
                <a href="<?= auth()->loggedIn() ? '/vote' : '/register' ?>" class="inline-block text-sm font-semibold text-primary mt-3">Vote on the next mountain →</a>
                <?php endif ?>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- Everything else -->
<section class="home-section bg-base-200 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <h2 class="sign mb-10">Also in the game</h2>
        <div class="feature-list">
            <div><b><i class="fa-solid fa-users"></i>Ten staff roles</b>Patrol, groomers, instructors, chefs and more.</div>
            <div><b><i class="fa-solid fa-cloud-sun"></i>Changing weather</b>Temperatures move through the day.</div>
            <div><b><i class="fa-solid fa-building-columns"></i>Loans and insurance</b>Borrow to grow, insure against bad luck.</div>
            <div><b><i class="fa-solid fa-truck-monster"></i>Real equipment</b>PistenBully groomers, TechnoAlpin guns.</div>
            <div><b><i class="fa-solid fa-person-snowboarding"></i>Terrain parks</b>Halfpipes, jumps and rail gardens.</div>
            <div><b><i class="fa-solid fa-ranking-star"></i>Leaderboard</b>See how your resort stacks up each season.</div>
            <div><b><i class="fa-solid fa-bolt"></i>Energy and water</b>Lifts and snow guns need both.</div>
            <div><b><i class="fa-solid fa-hotel"></i>Lodging and food</b>Hotels, restaurants, rentals and retail.</div>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="home-cta">
    <div class="max-w-6xl mx-auto px-4 py-14 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <?php if (auth()->loggedIn()) : ?>
        <div>
            <h2>Your lifts are still turning.</h2>
            <p class="mt-2" style="color:rgba(247,245,240,0.7)">The season keeps going while you're away.</p>
        </div>
        <a href="/dashboard" class="btn btn-primary px-8">Go to your resort</a>
        <?php else : ?>
        <div>
            <h2>The mountain's empty. Go build it.</h2>
            <p class="mt-2" style="color:rgba(247,245,240,0.7)">Free, in your browser, with up to <?= currency(1000000) ?> to start.</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="/register" class="btn btn-primary px-8">Play free</a>
            <a href="/login" class="text-sm underline underline-offset-4" style="color:rgba(247,245,240,0.8)">I already play</a>
        </div>
        <?php endif ?>
    </div>
</section>

<?= $this->endSection() ?>
