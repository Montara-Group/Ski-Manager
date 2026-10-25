<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        // Agent discovery (RFC 8288 Link headers, RFC 9727 section 3)
        $this->response->setHeader('Link', implode(', ', [
            '</.well-known/api-catalog>; rel="api-catalog"; type="application/linkset+json"',
            '</openapi.json>; rel="service-desc"; type="application/vnd.oai.openapi+json"',
            '</developers>; rel="service-doc"; type="text/html"',
            '</sitemap.xml>; rel="describedby"; type="text/xml"',
        ]));

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

