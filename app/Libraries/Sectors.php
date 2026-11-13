<?php

namespace App\Libraries;

/**
 * Which trail map sectors a player can build in. Seasons release sectors: an admin sets each
 * sector's release_season on /admin/seasons. A player on their own season (own-pace seasons) sees
 * every visible sector released in their season or before. Sectors without a release season, and
 * players on the shared calendar, keep the old global `released` switch.
 */
class Sectors
{
    public static function isOpen(array $sector, ?int $userId): bool
    {
        if (!(int) ($sector['visible'] ?? 0)) return false;
        $release = $sector['release_season'] ?? null;
        if ($userId && $release !== null && $release !== '' && Clock::enabledFor($userId)) {
            return (int) $release <= (int) Clock::as($userId, 'getSeasonNumber');
        }
        return (int) ($sector['released'] ?? 0) === 1;
    }

    /** Ids of the open sectors among $sectors (rows of resort_sectors) */
    public static function openIds(array $sectors, ?int $userId): array
    {
        return array_values(array_map(fn($s) => (int) $s['id'], array_filter($sectors, fn($s) => self::isOpen($s, $userId))));
    }
}
