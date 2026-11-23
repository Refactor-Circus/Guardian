<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * The sign-in identifier (`guardian.login.identifier_field`, `email` by
 * default) in what was typed, or of a user.
 */
final class Identifier
{
    public static function field(): string
    {
        $field = config('guardian.login.identifier_field', 'email');

        return is_string($field) && $field !== '' ? $field : 'email';
    }

    /**
     * @param  array<array-key, mixed>  $input
     */
    public static function in(array $input): ?string
    {
        $value = $input[self::field()] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function of(Authenticatable $user): ?string
    {
        $value = $user instanceof Model ? $user->getAttribute(self::field()) : null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
