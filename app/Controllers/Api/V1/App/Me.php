<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Me extends BaseController
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        $user = auth()->user();
        $db = db_connect();
        $finance = $db->table('player_finances')->select('cash, profile_completed')->where('user_id', $user->id)->get()->getRowArray();
        $genepis = $db->table('genepis')->select('balance')->where('user_id', $user->id)->get()->getRowArray();
        return $this->ok([
            'user' => ['id' => (int) $user->id, 'username' => (string) $user->username],
            'profileComplete' => (bool) ($finance['profile_completed'] ?? false),
            'stats' => [
                'cashText' => currency((int) ($finance['cash'] ?? 0)),
                'genepis' => (int) ($genepis['balance'] ?? 0),
                'gameDay' => getGameDay(),
                'seasonDay' => getSeasonDay(),
                'seasonLength' => getSeasonLength(),
            ],
            // Own-pace seasons: { enabled: false } while the resort follows the shared calendar
            'clock' => \App\Libraries\Clock::state((int) $user->id),
            'unread' => unreadNotificationCount((int) $user->id),
        ]);
    }
}
