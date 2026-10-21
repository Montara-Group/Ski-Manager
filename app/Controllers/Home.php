<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('home');
    }

    public function maintenance(): string
    {
        $info = function_exists('getMaintenanceInfo') ? getMaintenanceInfo() : [
            'enabled'           => true,
            'until'             => '2026-10-25 18:00:00 UTC',
            'message'           => 'Ski Manager is temporarily paused for 4 days of scheduled maintenance and active development for Season 2.',
            'remaining_seconds' => 345600,
        ];
        return view('maintenance', ['maintenance' => $info]);
    }
}

