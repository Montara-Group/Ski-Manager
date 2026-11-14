<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Closes the whole site to everyone but admins, for staging.ski-manager.net. Off unless the server's
 * .env sets `PRIVATE_SITE = true`, so production never runs it.
 *
 * Signing in stays open (login, auth, logout), so an admin can get in. Everyone else gets a short
 * "this site is private" page, and API calls get a 403. Responses carry noindex so search engines
 * drop the staging copy. CLI (spark, the game tick) doesn't pass through filters.
 */
class PrivateSite implements FilterInterface
{
    /** Path prefixes an admin needs before they are signed in */
    private const OPEN = ['login', 'logout', 'auth', 'health'];

    public static function enabled(): bool
    {
        return filter_var(env('PRIVATE_SITE', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!self::enabled()) {
            return;
        }

        $path = trim(uri_string(), '/');
        foreach (self::OPEN as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return;
            }
        }

        if ($this->isAdmin($this->sessionUser()) || $this->isAdmin($this->tokenUser($request))) {
            return;
        }

        $response = service('response')
            ->setStatusCode(403)
            ->setHeader('Cache-Control', 'no-store')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow');

        $accept = $request->getHeaderLine('Accept');
        if ($path === 'api' || str_starts_with($path, 'api/') || $request->isAJAX() || str_contains($accept, 'application/json')) {
            return $response->setJSON(['success' => false, 'message' => 'This site is private.']);
        }

        $signedIn = auth()->loggedIn();
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">'
            . '<title>Private site - Ski Manager</title>'
            . '<style ' . csp_style_nonce() . '>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#11151c;color:#e3e8f0;font:16px/1.5 system-ui,sans-serif;padding:16px}'
            . 'main{max-width:28rem}h1{font-size:1.5rem;margin:0 0 .5rem}p{color:#aab3c2;margin:0 0 1rem}a{color:#8cbcff}a:focus-visible{outline:2px solid #8cbcff;outline-offset:2px}</style>'
            . '</head><body><main><h1>This site is private</h1>'
            . '<p>This is the Ski Manager test server. The game is at <a href="https://ski-manager.net">ski-manager.net</a>.</p>'
            . ($signedIn
                ? '<p>Your account doesn\'t have access here. <a href="/logout">Log out</a></p>'
                : '<p><a href="/login">Log in</a> if you have access.</p>')
            . '</main></body></html>';

        return $response->setBody($html);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (self::enabled()) {
            $response->setHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    private function sessionUser()
    {
        return auth()->loggedIn() ? auth()->user() : null;
    }

    /** The mobile apps send a bearer token instead of a session */
    private function tokenUser(RequestInterface $request)
    {
        if (!str_starts_with($request->getHeaderLine('Authorization'), 'Bearer ')) {
            return null;
        }
        $tokens = auth('tokens')->getAuthenticator();
        $token = (string) $tokens->getBearerToken();
        return $token !== '' && $tokens->attempt(['token' => $token])->isOK() ? $tokens->getUser() : null;
    }

    private function isAdmin($user): bool
    {
        return $user !== null && method_exists($user, 'inGroup') && $user->inGroup('admin', 'superadmin');
    }
}
