<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Support;

use SensitiveParameter;

/**
 * How recovery codes and one-time codes are kept: an HMAC-SHA256 keyed by
 * the app key, so a leaked table reveals no code, and compared in constant
 * time.
 */
final class CodeHash
{
    public static function of(#[SensitiveParameter] string $code, string $context = ''): string
    {
        $key = config('app.key');

        return hash_hmac('sha256', $context.'|'.$code, is_string($key) ? $key : '');
    }

    public static function matches(string $hash, #[SensitiveParameter] string $code, string $context = ''): bool
    {
        return hash_equals($hash, self::of($code, $context));
    }
}
