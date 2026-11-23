<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures\webauthn;

/**
 * A CBOR byte string (major type 2), as opposed to a text string.
 */
final readonly class CborBytes
{
    public function __construct(public string $bytes) {}
}
