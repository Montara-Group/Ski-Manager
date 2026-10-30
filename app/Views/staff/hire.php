<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Hire Staff<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$categories = [
    'Slopes & lifts' => ['groomer', 'mechanic', 'ski_patrol', 'medic', 'snowmaker', 'park_crew'],
    'Guests & buildings' => ['instructor', 'chef', 'receptionist', 'manager'],
];
$verdicts = [
    'needed' => ['badge-error', 'Needed'], 'useful' => ['badge-success', 'Useful'],
    'enough' => ['badge-ghost', 'Enough for now'], 'none' => ['badge-ghost', 'No post yet'],
];
$needed = array_filter($roles, fn($r) => $r['verdict'] === 'needed');
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="/staff" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to staff"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-user-plus mr-2 text-primary" aria-hidden="true"></i>Hire staff</h1>
                <p class="text-sm text-base-content/60">No hiring fee: you pay a daily salary from tonight. New hires start at level 1 on the first free post.</p>
            </div>
        </div>
        <div class="text-sm text-right">
            <div>Cash <span class="font-bold <?= $cash < 0 ? 'text-error' : 'text-success' ?>"><?= currency($cash) ?></span></div>
            <div class="text-base-content/60"><?= $staffCount ?> staff · <?= currency($payroll) ?>/day</div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= esc(session('error')) ?></span></div><?php endif ?>

    <?php if ($needed) : ?>
    <div class="alert alert-warning mb-4">
        <i class="fa-solid fa-user-clock" aria-hidden="true"></i>
        <span><b>Your resort needs:</b> <?= esc(implode(', ', array_map(fn($r) => $r['name'], $needed))) ?>.</span>
    </div>
    <?php endif ?>

    <?php foreach ($categories as $catName => $keys) : ?>
    <section class="mb-6" aria-labelledby="cat-<?= md5($catName) ?>">
        <h2 id="cat-<?= md5($catName) ?>" class="text-sm font-semibold text-base-content/60 uppercase tracking-wider mb-3"><?= $catName ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <?php foreach ($keys as $key) : if (!isset($roles[$key])) continue; $r = $roles[$key]; [$badge, $label] = $verdicts[$r['verdict']]; $dim = in_array($r['verdict'], ['enough', 'none'], true); ?>
            <div class="card bg-base-100 shadow-sm <?= $r['verdict'] === 'needed' ? 'border border-error/40' : '' ?>"><div class="card-body p-4 gap-2">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-xl bg-base-200 flex items-center justify-center shrink-0"><i class="<?= esc($r['icon'], 'attr') ?> text-xl <?= esc($r['color'], 'attr') ?>" aria-hidden="true"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="font-bold"><?= esc($r['name']) ?></h3>
                            <span class="badge badge-sm <?= $badge ?> shrink-0"><?= $label ?></span>
                        </div>
                        <p class="text-sm text-base-content/80 mt-1"><?= esc($r['effect']) ?></p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-base-content/60">
                    <span>You have <b class="text-base-content"><?= $r['have'] ?></b><?= $r['have'] ? ' (' . $r['assigned'] . ' working)' : '' ?></span>
                    <?php if ($key !== 'manager') : ?><span><?= $r['posts'] ?> post<?= $r['posts'] === 1 ? '' : 's' ?></span><?php endif ?>
                    <span class="<?= $r['verdict'] === 'needed' ? 'text-error' : '' ?>"><?= esc($r['why']) ?></span>
                </div>
                <form action="/staff/hire" method="post" class="flex items-center justify-between gap-2 border-t border-base-300 pt-2 mt-1"
                      data-confirm="Hire a <?= esc($r['name']) ?> for <?= currency($r['daily']) ?> a day?<?= $dim ? ' ' . esc($r['why']) : '' ?>"><?= csrf_field() ?>
                    <input type="hidden" name="role" value="<?= esc($key) ?>">
                    <span class="font-bold"><?= currency($r['daily']) ?><span class="font-normal text-xs text-base-content/60">/day</span></span>
                    <button class="btn btn-sm gap-1 <?= $dim ? 'btn-ghost' : 'btn-primary' ?>"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Hire</button>
                </form>
            </div></div>
        <?php endforeach ?>
        </div>
    </section>
    <?php endforeach ?>

    <details class="card bg-base-100 shadow-sm">
        <summary class="card-body p-4 cursor-pointer font-semibold text-sm">How staff work</summary>
        <ul class="px-4 pb-4 text-sm text-base-content/80 space-y-1 list-disc pl-9">
            <li>Salaries are paid every night, whether or not the staff member has work.</li>
            <li>Most roles only help while assigned to a post (a slope, lift, building, snow gun or park). Assign or reassign them on the <a href="/staff" class="link link-primary">staff page</a>.</li>
            <li>Morale drops a little every day; working staff lose less than idle ones. Below 10% they may quit.</li>
            <li>Training raises a level. For groomer operators each level restores a little more slope condition per night.</li>
        </ul>
    </details>
</div>
<?= $this->endSection() ?>
