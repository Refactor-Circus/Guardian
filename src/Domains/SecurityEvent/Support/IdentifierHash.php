<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Support;

/**
 * How a sign-in identifier (an email, a username) is kept: an HMAC-SHA256 of
 * it trimmed and lowercased, keyed by the app key, so the log can tell
 * attempts at the same identifier apart without storing what was typed.
 */
final class IdentifierHash
{
    public static function of(string $identifier): string
    {
        $key = config('app.key');

        return hash_hmac('sha256', mb_strtolower(trim($identifier)), is_string($key) ? $key : '');
    }
}
