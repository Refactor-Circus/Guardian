<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Support;

/**
 * The network an address belongs to for suspicious sign-ins (criterion
 * 51): its /24 for IPv4, its /48 for IPv6.
 */
final class Networks
{
    public static function of(?string $ip): ?string
    {
        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = inet_pton($ip);

        if ($packed === false) {
            return null;
        }

        return strlen($packed) === 4
            ? bin2hex(substr($packed, 0, 3)).'/24'
            : bin2hex(substr($packed, 0, 6)).'/48';
    }
}
