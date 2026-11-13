<?php

namespace App\Libraries;

use Config\Database;

class GameLogger
{
    public static function log(string $category, string $message, ?int $day = null, ?int $userId = null, string $icon = 'fa-solid fa-circle-info')
    {
        $db = Database::connect();
        $finalUserId = $userId ?? auth()->id() ?? null;

        if ($day === null) {
            $day = $finalUserId ? getGameDayFor((int) $finalUserId) : getGameDay();
        }

        if ($finalUserId) {
            $db->table('activity_log')->insert([
                'user_id'    => $finalUserId,
                'game_day'   => $day,
                'category'   => ucfirst($category),
                'message'    => $message,
                'icon'       => $icon,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
