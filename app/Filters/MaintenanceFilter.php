<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceFilter implements FilterInterface
{
    /**
     * URL path prefixes exempt from maintenance mode.
     */
    protected array $exemptPrefixes = [
        'health',
        'maintenance',
        'login',
        'auth',
        'logout',
        'admin',
        'register', // Allow reaching auth controller if needed for admin verification
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!function_exists('isMaintenanceMode') || !isMaintenanceMode()) {
            return;
        }

        $uriPath = trim(uri_string(), '/');

        // Always allow system health checks for CI/CD and uptime monitors
        if ($uriPath === 'health' || str_starts_with($uriPath, 'health/')) {
            return;
        }

        // Allow authenticated administrators full site access to build & test Season 2
        if (function_exists('auth') && auth()->loggedIn()) {
            $user = auth()->user();
            $isAdmin = auth()->id() === 1 || ($user && method_exists($user, 'inGroup') && $user->inGroup('admin', 'superadmin'));
            if ($isAdmin) {
                return;
            }
        }

        // Allow access to login/auth and admin routes
        foreach ($this->exemptPrefixes as $prefix) {
            if ($uriPath === $prefix || str_starts_with($uriPath, $prefix . '/')) {
                return;
            }
        }

        // Assemble maintenance information
        $info = function_exists('getMaintenanceInfo') ? getMaintenanceInfo() : [
            'enabled'           => true,
            'until'             => '2026-10-25 18:00:00 UTC',
            'message'           => 'Ski Manager is temporarily paused for 4 days of scheduled maintenance and active development for Season 2.',
            'remaining_seconds' => 345600,
        ];

        $retryAfter = max(60, (int) ($info['remaining_seconds'] ?? 345600));

        $response = service('response');
        $response->setStatusCode(503, 'Service Unavailable')
                 ->setHeader('Retry-After', (string) $retryAfter)
                 ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                 ->setHeader('Pragma', 'no-cache');

        // Check for AJAX / JSON requests
        $accept = method_exists($request, 'getHeaderLine') ? $request->getHeaderLine('Accept') : '';
        if ($request->isAJAX() || str_contains($accept, 'application/json')) {
            return $response->setJSON([
                'status'            => 'maintenance',
                'maintenance'       => true,
                'message'           => $info['message'],
                'until'             => $info['until'],
                'remaining_seconds' => $retryAfter,
            ]);
        }

        return $response->setBody(view('maintenance', ['maintenance' => $info]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
