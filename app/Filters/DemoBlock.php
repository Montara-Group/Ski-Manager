<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Actions that reach other players are for saved resorts: a demo costs nothing to create, so votes
 * and likes from demos could be stuffed, and alliances and tournaments would fill with throwaway
 * resorts. The routes are listed in Config\Filters. Off until the `demo_v2` flag is on.
 */
class DemoBlock implements FilterInterface
{
    private const MESSAGES = [
        'alliances'   => 'Alliances are for saved resorts. Save your resort to join one.',
        'tournaments' => 'Hosting tournaments is for saved resorts. Save your resort to host one.',
        'vote'        => 'Voting is for saved resorts. Save your resort to vote.',
        'roadmap'     => 'Voting is for saved resorts. Save your resort to vote.',
        'tour'        => 'Likes are for saved resorts. Save your resort to like this one.',
        'support'     => 'Support chat is for saved resorts. Use the contact form instead.',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtolower($request->getMethod()) !== 'post' || !featureEnabled('demo_v2') || !auth()->loggedIn()) {
            return;
        }
        if ((string) (auth()->user()->status ?? '') !== 'demo') {
            return;
        }

        $section = explode('/', trim($request->getUri()->getPath(), '/'))[0];
        $message = self::MESSAGES[$section] ?? 'This is for saved resorts. Save your resort first.';

        if ($request->isAJAX()) {
            return service('response')->setStatusCode(403)->setJSON(['success' => false, 'message' => $message]);
        }
        return redirect()->back()->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
