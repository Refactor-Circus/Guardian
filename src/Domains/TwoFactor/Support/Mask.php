<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Support;

use Illuminate\Support\Str;

/**
 * How a method's destination is shown (criterion 31): an email keeps its
 *
 * first character and its domain (`a***@example.com`), a phone number its
 * last two digits (`*********34`).
 */
final class Mask
{
    public static function destination(?string $destination): ?string
    {
        if ($destination === null || $destination === '') {
            return null;
        }

        if (str_contains($destination, '@')) {
            [$local, $domain] = [Str::beforeLast($destination, '@'), Str::afterLast($destination, '@')];

            return mb_substr($local, 0, 1).'***@'.$domain;
        }

        $length = mb_strlen($destination);

        return str_repeat('*', max($length - 2, 3)).mb_substr($destination, -2);
    }
}
