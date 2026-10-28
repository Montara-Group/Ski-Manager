<?php

namespace App\Filters;

use CodeIgniter\Filters\SecureHeaders as BaseSecureHeaders;

/**
 * CodeIgniter's secure headers, with the browser-default Referrer-Policy instead of "same-origin".
 * "same-origin" strips the page address from requests to Google's ad servers, which AdSense
 * needs; "strict-origin-when-cross-origin" still never sends paths or query strings off-site.
 */
class SecureHeaders extends BaseSecureHeaders
{
    protected $headers = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-Download-Options' => 'noopen',
        'X-Permitted-Cross-Domain-Policies' => 'none',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];
}
