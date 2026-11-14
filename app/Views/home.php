<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Free Online Ski Resort Tycoon Game<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// The hero, rendered here so search engines and slow phones get the headline and the way in before
// the HomePage island mounts. Same markup and classes as Hero() in resources/js/islands/HomePage.tsx,
// so nothing moves when React takes over.
$s = $page['season'];
$w = $page['weather'];
$heroSecondary = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-white/40 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10 active:bg-white/15 transition-colors';
$primary = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-accent px-5 py-2.5 text-sm font-semibold text-accent-foreground hover:bg-[var(--accent-hover)] active:translate-y-px transition-colors';
ob_start();
?>
<section class="relative isolate overflow-hidden bg-[#0a0e14] text-white" aria-labelledby="home-title">
    <picture><source media="(min-width: 1024px)" srcset="/img/ParkCity_med.jpg"><img src="/img/ParkCity_low.jpg" alt="" width="800" height="533" fetchpriority="high" decoding="async" class="absolute inset-0 -z-20 size-full object-cover"></picture>
    <div class="absolute inset-0 -z-10 bg-[#0a0e14]/85 lg:bg-transparent lg:bg-gradient-to-r lg:from-[#0a0e14]/95 lg:via-[#0a0e14]/85 lg:to-[#0a0e14]/45"></div>
    <div class="mx-auto grid max-w-6xl grid-cols-1 items-center gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-12 lg:px-8 lg:py-20">
        <div class="space-y-6 lg:col-span-7">
            <p class="flex items-center gap-2 text-sm text-white/85">
                <span class="size-2 rounded-full bg-[oklch(72%_0.18_155)]" aria-hidden="true"></span>
                <span>Season <?= (int) $s['number'] ?>, day <?= (int) $s['day'] ?> of <?= (int) $s['length'] ?></span>
            </p>
            <h1 id="home-title" class="max-w-xl text-[clamp(2.125rem,5vw,3.25rem)] font-bold leading-[1.1] tracking-tight">
                Run a ski resort on a <span class="text-[oklch(80%_0.11_245)]">real mountain.</span>
            </h1>
            <p class="max-w-lg text-base leading-relaxed text-white/85 sm:text-lg">
                Ski Manager is a free browser game. You get the Park City trail map, a starting budget and a <?= (int) $s['length'] ?>-day
                season. Build lifts and runs, hire staff, set ticket prices, and keep the snow good enough that skiers come back.
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <?php if ($page['isLoggedIn']): ?>
                    <a href="/dashboard" class="<?= $primary ?>">Open your resort</a>
                    <a href="/map" class="<?= $heroSecondary ?>">Trail map</a>
                <?php else: ?>
                    <a href="/register" class="<?= $primary ?>">Play free</a>
                    <a href="/login" class="inline-flex min-h-11 items-center px-2 text-sm font-medium text-white/85 underline underline-offset-4 hover:text-white">Log in</a>
                <?php endif; ?>
            </div>
            <p class="text-sm text-white/75">
                <strong class="font-semibold text-white tabular-nums"><?= esc($page['players']) ?></strong> managers ·
                <strong class="font-semibold text-white tabular-nums"><?= esc($page['buildersThisWeek']) ?></strong> built something this week ·
                <strong class="font-semibold text-white tabular-nums"><?= esc($page['runsBuilt']) ?></strong> runs built so far
            </p>
        </div>
        <?php if ($w): ?>
        <div class="lg:col-span-5">
            <div class="snow-board overflow-hidden rounded-xl">
                <div class="snow-board-strip flex items-baseline justify-between gap-3 px-4 py-2.5">
                    <h2 class="text-sm font-bold uppercase tracking-wider">Snow report</h2>
                    <span class="text-xs">Park City · day <?= (int) $s['day'] ?></span>
                </div>
                <dl class="grid grid-cols-2">
                    <div class="snow-board-cell px-4 py-3"><dt class="text-xs">Summit</dt><dd class="text-3xl font-bold tabular-nums"><?= esc($w['temp']) ?></dd><dd class="text-sm"><?= esc($w['condition']) ?></dd></div>
                    <div class="snow-board-cell px-4 py-3"><dt class="text-xs">Base depth</dt><dd class="text-3xl font-bold tabular-nums"><?= esc($w['snowBase']) ?></dd><dd class="text-sm">New snow: <?= esc($w['newSnow']) ?></dd></div>
                </dl>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php $fallback = ob_get_clean(); ?>
<?= island('HomePage', $page, $fallback) ?>
<?= $this->endSection() ?>
