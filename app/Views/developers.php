<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>API<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $__endpoints = [
        ['GET', '/api/resort-data', 'Trail map segments and sectors, the current season and weather. With a ski-manager.net session cookie it also returns your finances, lifts, slopes and buildings; without one, finances are a demo baseline and the player lists are empty.'],
        ['GET', '/api/leaderboard', 'The top 50 resorts, ranked by cash plus lifetime income.'],
        ['POST', '/api/auth', 'Demo sign-in used by the mobile apps. Returns a random token. It does not check credentials, and no other endpoint needs the token.'],
        ['POST', '/api/sync', 'Acknowledges a sync from the mobile apps and echoes back the cash you send. Nothing is saved.'],
        ['GET', '/health', 'Database and storage checks. Returns 200 when healthy and 503 when something is down.'],
    ];
?>
<style>
.dev { max-width: 52rem; margin: 0 auto; padding: 2.5rem 1rem 4rem; }
.dev h1 { font-family: var(--sign); font-size: 2rem; font-weight: 700; letter-spacing: -0.025em; line-height: 1.15; margin: 0 0 0.75rem; }
.dev h2 { font-family: var(--sign); font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; margin: 2.25rem 0 0.75rem; }
.dev p { line-height: 1.7; color: color-mix(in oklab, var(--color-base-content) 80%, transparent); }
.dev code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.85em; padding: 0.1rem 0.35rem; border-radius: 4px; background: var(--color-base-300); }
.dev pre { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.8125rem; line-height: 1.6; padding: 0.9rem 1rem; border-radius: 6px; background: var(--color-base-300); overflow-x: auto; }
.dev a { color: var(--color-primary); }
.ep { border-top: 1px solid var(--color-base-300); padding: 0.9rem 0; }
.ep:first-of-type { border-top: 0; }
.ep-head { display: flex; align-items: baseline; gap: 0.6rem; flex-wrap: wrap; }
.ep-method { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.75rem; font-weight: 700; width: 3rem; color: var(--color-primary); }
.ep-path { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.9375rem; font-weight: 600; }
.ep p { margin: 0.3rem 0 0 3.6rem; font-size: 0.9375rem; }
@media (max-width: 480px) { .ep p { margin-left: 0; } }
</style>

<div class="dev">
    <h1>Ski Manager API</h1>
    <p>A small JSON API, mainly used by the Ski Manager mobile apps. Everything is under <code><?= esc($base) ?></code>, responses are JSON, and cross-origin requests are allowed.</p>
    <p>Machine-readable description: <a href="/openapi.json">/openapi.json</a> (OpenAPI 3.1). API catalog for automated discovery: <a href="/.well-known/api-catalog">/.well-known/api-catalog</a> (RFC 9727).</p>

    <h2>Endpoints</h2>
    <?php foreach ($__endpoints as [$__m, $__p, $__d]) : ?>
    <div class="ep">
        <div class="ep-head"><span class="ep-method"><?= $__m ?></span><span class="ep-path"><?= esc($__p) ?></span></div>
        <p><?= esc($__d) ?></p>
    </div>
    <?php endforeach ?>

    <h2>Example</h2>
    <pre>curl <?= esc($base) ?>/api/leaderboard</pre>

    <h2>Limits</h2>
    <p>There are no API keys. Please keep it to a request every few seconds; the game itself only updates once a day, when the nightly tick runs. If you build something with it, <a href="/contact">tell us</a>.</p>
</div>
<?= $this->endSection() ?>
