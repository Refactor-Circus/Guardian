<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures\webauthn;

use InvalidArgumentException;

/**
 * Just enough CBOR (RFC 8949) to build attestation objects and COSE keys:
 * integers, byte and text strings, and maps.
 */
final class Cbor
{
    public static function encode(mixed $value): string
    {
        return match (true) {
            is_int($value) && $value >= 0 => self::head(0, $value),
            is_int($value) => self::head(1, -1 - $value),
            $value instanceof CborBytes => self::head(2, strlen($value->bytes)).$value->bytes,
            is_string($value) => self::head(3, strlen($value)).$value,
            is_array($value) => self::map($value),
            default => throw new InvalidArgumentException('Unsupported CBOR value.'),
        };
    }

    /**
     * @param  array<array-key, mixed>  $map
     */
    private static function map(array $map): string
    {
        $encoded = self::head(5, count($map));

        foreach ($map as $key => $item) {
            $encoded .= self::encode($key).self::encode($item);
        }

        return $encoded;
    }

    private static function head(int $major, int $length): string
    {
        $type = $major << 5;

        return match (true) {
            $length < 24 => chr($type | $length),
            $length < 0x100 => chr($type | 24).chr($length),
            $length < 0x10000 => chr($type | 25).pack('n', $length),
            default => chr($type | 26).pack('N', $length),
        };
    }
}
