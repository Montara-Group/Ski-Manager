<?php

/** Same rule as AdminFilter: user #1 or anyone in the admin/superadmin groups. False in CLI (no signed-in user). */
function featureViewerIsAdmin(): bool
{
    if (!function_exists('auth') || !auth()->loggedIn()) return false;
    $user = auth()->user();
    return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
}

/**
 * Flag levels: 0 = off, 1 = admins only, 2 = everyone.
 * Unknown keys count as on, so a feature isn't hidden just because its flag row is missing.
 */
function featureEnabled(string $key): bool
{
    static $cache = null;
    if ($cache === null) {
        $rows = db_connect()->table('feature_flags')->get()->getResultArray();
        $cache = [];
        foreach ($rows as $r) $cache[$r['flag_key']] = (int) $r['enabled'];
    }
    $level = $cache[$key] ?? 2;
    if ($level === 2) return true;
    if ($level === 1) return featureViewerIsAdmin();
    return false;
}
