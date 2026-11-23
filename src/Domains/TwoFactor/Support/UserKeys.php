<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/**
 * A user's key as the two-factor tables store it.
 */
final class UserKeys
{
    public static function of(Authenticatable $user): int|string
    {
        $key = $user->getAuthIdentifier();

        return is_int($key) || is_string($key) ? $key : throw new InvalidArgumentException('A user key must be an int or a string.');
    }
}
