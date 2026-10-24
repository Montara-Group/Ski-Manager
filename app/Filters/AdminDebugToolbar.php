<?php

namespace App\Filters;

use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Debug toolbar that only renders for admins, so a server left in development
 * mode (e.g. staging) doesn't expose queries, request data and config to visitors.
 */
class AdminDebugToolbar extends DebugToolbar
{
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (!function_exists('auth') || !auth()->loggedIn()) {
            return null;
        }

        $user = auth()->user();
        if (auth()->id() !== 1 && !($user && $user->inGroup('admin', 'superadmin'))) {
            return null;
        }

        return parent::after($request, $response, $arguments);
    }
}
