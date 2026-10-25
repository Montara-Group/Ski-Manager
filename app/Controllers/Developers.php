<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Public API discovery: RFC 9727 API catalog, OpenAPI description and human docs.
 * URLs are built from base_url() so staging advertises staging, not production.
 */
class Developers extends BaseController
{
    /** GET|HEAD /.well-known/api-catalog (RFC 9727, Linkset format per RFC 9264) */
    public function apiCatalog(): ResponseInterface
    {
        $base = rtrim(base_url(), '/');

        $catalog = [
            'linkset' => [[
                'anchor'       => $base . '/api',
                'service-desc' => [['href' => $base . '/openapi.json', 'type' => 'application/vnd.oai.openapi+json']],
                'service-doc'  => [['href' => $base . '/developers', 'type' => 'text/html']],
                'status'       => [['href' => $base . '/health', 'type' => 'application/json']],
            ]],
        ];

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/linkset+json; profile="https://www.rfc-editor.org/info/rfc9727"')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setBody(json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /** GET /openapi.json */
    public function openapi(): ResponseInterface
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Cache-Control', 'public, max-age=3600')
            ->setContentType('application/json')
            ->setBody(json_encode($this->spec(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /** GET /developers */
    public function index(): string
    {
        return view('developers', ['base' => rtrim(base_url(), '/')]);
    }

    private function spec(): array
    {
        $json = fn(array $schema, string $description = 'OK') => [
            'description' => $description,
            'content'     => ['application/json' => ['schema' => $schema]],
        ];
        $obj = fn(array $props, array $required = []) => array_filter([
            'type' => 'object', 'properties' => $props, 'required' => $required ?: null,
        ], fn($v) => $v !== null);

        return [
            'openapi' => '3.1.0',
            'info'    => [
                'title'       => 'Ski Manager API',
                'version'     => '1.0.0',
                'description' => 'Read-only game data used by the Ski Manager mobile apps, plus a health check. '
                    . 'Endpoints return the signed-in player\'s data when called with a ski-manager.net session cookie, '
                    . 'and public or demo data otherwise.',
                'contact'     => ['name' => 'Ski Manager', 'url' => rtrim(base_url(), '/') . '/contact'],
            ],
            'servers' => [['url' => rtrim(base_url(), '/')]],
            'paths'   => [
                '/api/resort-data' => ['get' => [
                    'operationId' => 'getResortData',
                    'summary'     => 'Trail map, season, weather and (when signed in) your resort',
                    'description' => 'Returns the active map segments and sectors, the current season and weather. '
                        . 'With a session cookie it also includes your finances, lifts, slopes and buildings; '
                        . 'without one, finances are a demo baseline and the player arrays are empty.',
                    'responses'   => ['200' => $json($obj([
                        'status'        => ['type' => 'string', 'example' => 'success'],
                        'server_time'   => ['type' => 'integer', 'description' => 'Unix timestamp'],
                        'resort_map'    => ['type' => 'string', 'example' => 'ParkCity'],
                        'map_image_url' => ['type' => 'string', 'format' => 'uri'],
                        'season'        => ['type' => 'object'],
                        'game_day'      => ['type' => 'integer'],
                        'finances'      => ['type' => 'object'],
                        'segments'      => ['type' => 'array', 'items' => $obj([
                            'id'            => ['type' => 'integer'],
                            'type'          => ['type' => 'string', 'description' => 'lift, downhill, snowpark, ...'],
                            'difficulty'    => ['type' => ['string', 'null'], 'enum' => ['green', 'blue', 'black', 'double_black', null]],
                            'name'          => ['type' => 'string'],
                            'points'        => ['type' => 'array', 'items' => ['type' => 'array', 'items' => ['type' => 'number']]],
                            'length_meters' => ['type' => 'integer'],
                            'sector'        => ['type' => 'integer'],
                        ])],
                        'sectors'       => ['type' => 'array', 'items' => ['type' => 'object']],
                        'player_lifts'  => ['type' => 'array', 'items' => ['type' => 'object']],
                        'player_slopes' => ['type' => 'array', 'items' => ['type' => 'object']],
                        'buildings'     => ['type' => 'array', 'items' => ['type' => 'object']],
                        'weather'       => ['type' => 'object'],
                    ], ['status', 'segments', 'sectors']))],
                ]],
                '/api/leaderboard' => ['get' => [
                    'operationId' => 'getLeaderboard',
                    'summary'     => 'Top 50 resorts',
                    'description' => 'Ranked by cash plus lifetime income.',
                    'responses'   => ['200' => $json(['type' => 'array', 'items' => $obj([
                        'id'           => ['type' => 'integer'],
                        'username'     => ['type' => 'string'],
                        'resortName'   => ['type' => 'string'],
                        'cash'         => ['type' => 'integer'],
                        'reputation'   => ['type' => 'integer'],
                        'rankPosition' => ['type' => 'integer'],
                    ], ['id', 'username', 'cash', 'rankPosition'])])],
                ]],
                '/api/auth' => ['post' => [
                    'operationId' => 'mobileDemoAuth',
                    'summary'     => 'Demo sign-in for the mobile apps',
                    'description' => 'Returns a random token for the mobile demo. It does not check credentials, '
                        . 'and no other endpoint requires the token.',
                    'requestBody' => ['required' => false, 'content' => ['application/json' => ['schema' => $obj([
                        'username' => ['type' => 'string'],
                    ])]]],
                    'responses'   => ['200' => $json($obj([
                        'status'   => ['type' => 'string', 'example' => 'authenticated'],
                        'token'    => ['type' => 'string'],
                        'username' => ['type' => 'string'],
                        'server'   => ['type' => 'string', 'format' => 'uri'],
                    ]))],
                ]],
                '/api/sync' => ['post' => [
                    'operationId' => 'mobileSync',
                    'summary'     => 'Mobile sync acknowledgement',
                    'description' => 'Acknowledges a sync from the mobile apps and echoes back the cash value sent. Nothing is saved.',
                    'requestBody' => ['required' => false, 'content' => ['application/json' => ['schema' => $obj([
                        'cash'     => ['type' => 'integer'],
                        'game_day' => ['type' => 'integer'],
                    ])]]],
                    'responses'   => ['200' => $json($obj([
                        'status'          => ['type' => 'string', 'example' => 'success'],
                        'synced_at'       => ['type' => 'string'],
                        'server_time'     => ['type' => 'integer'],
                        'server_game_day' => ['type' => 'integer'],
                        'confirmed_cash'  => ['type' => 'integer'],
                    ]))],
                ]],
                '/health' => ['get' => [
                    'operationId' => 'getHealth',
                    'summary'     => 'Service health',
                    'description' => 'Database and storage checks. 200 when healthy, 503 when degraded.',
                    'responses'   => [
                        '200' => $json($obj([
                            'status'    => ['type' => 'string', 'enum' => ['ok', 'degraded']],
                            'app'       => ['type' => 'string'],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                            'checks'    => ['type' => 'object'],
                        ], ['status'])),
                        '503' => ['description' => 'Degraded: a check failed'],
                    ],
                ]],
            ],
        ];
    }
}
