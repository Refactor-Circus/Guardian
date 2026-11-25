<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Support;

/**
 * A security event's context with every secret taken out (D-019): any key
 * naming a password, code, secret, token, recovery code, assertion or
 * credential is dropped, at any depth, before it is stored or announced.
 */
final class SecretlessContext
{
    /**
     * Key fragments that mark a secret, compared case-insensitively.
     *
     * @var list<string>
     */
    public const array SECRET_KEYS = ['password', 'code', 'secret', 'token', 'recovery', 'otp', 'assertion', 'credential', 'remember'];

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<array-key, mixed>
     */
    public static function of(array $context): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            if (is_string($key) && self::secret($key)) {
                continue;
            }

            $clean[$key] = is_array($value) ? self::of($value) : (is_scalar($value) || $value === null ? $value : get_debug_type($value));
        }

        return $clean;
    }

    private static function secret(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SECRET_KEYS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
