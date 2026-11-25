<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Support;

/**
 * An IPv4 or IPv6 address, or a CIDR range of one (`203.0.113.0/24`,
 * `2001:db8::/32`), as IP rules take them.
 */
final class Cidr
{
    public static function valid(string $cidr): bool
    {
        $parts = explode('/', $cidr);

        if (count($parts) > 2 || filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if (count($parts) === 1) {
            return true;
        }

        $max = filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false ? 128 : 32;

        return ctype_digit($parts[1]) && (int) $parts[1] <= $max;
    }
}
