<?php

namespace App\Support;

/**
 * Gmail's SMTP occasionally drops the very first connection of a request
 * with an empty EHLO response (observed for the verification email, which
 * is always the first of two connections register() opens) while a second,
 * separate connection moments later succeeds. One retry absorbs that kind
 * of transient handshake flake instead of silently losing the mail.
 */
class MailRetry
{
    public static function attempt(callable $send, int $times = 2, int $delayMs = 400): bool
    {
        for ($try = 1; $try <= $times; $try++) {
            try {
                $send();

                return true;
            } catch (\Throwable $e) {
                if ($try === $times) {
                    report($e);

                    return false;
                }

                usleep($delayMs * 1000);
            }
        }

        return false;
    }
}
