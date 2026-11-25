<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Data;

/**
 * What a sign-out of several sessions did: how many sessions were revoked,
 * and how many API tokens (null when tokens were not asked for).
 */
final readonly class SessionsRevoked
{
    public function __construct(
        public int $sessions,
        public ?int $tokens = null,
    ) {}
}
