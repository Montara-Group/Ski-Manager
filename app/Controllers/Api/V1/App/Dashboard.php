<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Dashboard extends \App\Controllers\Dashboard
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        $page = $this->pageData();
        return $this->ok([
            'hero' => $page['hero'],
            'alerts' => $page['alerts'],
            'kpis' => $page['kpis'],
            'estimate' => $page['demoEstimate'] ?? null,
            // Own-pace seasons: { enabled: false } on the shared calendar (App\Libraries\Clock::state)
            'clock' => \App\Libraries\Clock::state((int) auth()->id()),
        ]);
    }
}
