<?php

namespace App\Authentication\Actions;

use CodeIgniter\Shield\Authentication\Actions\EmailActivator as ShieldEmailActivator;
use CodeIgniter\Shield\Exceptions\RuntimeException;

/**
 * Shield's activator emails a new code on every visit to /auth/a/show and throws (a 500)
 * when the mail can't be sent, which left players stuck in an error loop. This one emails
 * a code once, lets the player ask for another after RESEND_AFTER seconds, and says so
 * plainly when sending fails.
 */
class EmailActivator extends ShieldEmailActivator
{
    public const RESEND_AFTER = 60;

    public function show(): string
    {
        $user = auth('session')->getAuthenticator()->getPendingUser();
        $view = setting('Auth.views')['action_email_activate_show'];
        if ($user === null) {
            return $this->view($view, ['user' => null]);
        }

        $session = session();
        $sent = $session->get('activation_mail');
        $sent = is_array($sent) && ($sent['user'] ?? null) === $user->id ? $sent : null;
        $wantsResend = service('request')->getGet('resend') !== null;

        // A code already went out: reloading the page shouldn't email another
        if ($sent !== null && $sent['ok'] && !$wantsResend) {
            return $this->view($view, ['user' => $user, 'mailNotice' => 'sent']);
        }
        if ($sent !== null && $sent['at'] > time() - self::RESEND_AFTER) {
            return $this->view($view, ['user' => $user, 'mailNotice' => $sent['ok'] ? 'sent' : 'failed',
                'retryIn' => $sent['at'] + self::RESEND_AFTER - time()]);
        }

        try {
            parent::show(); // creates a fresh code and emails it
            $session->set('activation_mail', ['user' => $user->id, 'at' => time(), 'ok' => true]);
            return $this->view($view, ['user' => $user, 'mailNotice' => $wantsResend ? 'resent' : 'sent']);
        } catch (RuntimeException $e) {
            // The exception text carries the address and SMTP transcript; log just the server's reply
            preg_match_all('/\b[45]\d\d[ -][^\n<]*/', $e->getMessage(), $m);
            log_message('error', 'Activation email failed for user #' . $user->id . ': ' . (end($m[0]) ?: 'no SMTP reply'));
            $session->set('activation_mail', ['user' => $user->id, 'at' => time(), 'ok' => false]);
            return $this->view($view, ['user' => $user, 'mailNotice' => 'failed', 'retryIn' => self::RESEND_AFTER]);
        }
    }
}
