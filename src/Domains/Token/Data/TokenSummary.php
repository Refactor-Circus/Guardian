<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Data;

use Carbon\CarbonImmutable;

/**
 * One of a user's API tokens as a driver describes it, never with its value.
 */
final readonly class TokenSummary
{
    /**
     * @param  list<string>  $abilities
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $abilities,
        public ?CarbonImmutable $lastUsedAt,
        public ?CarbonImmutable $expiresAt,
        public ?CarbonImmutable $createdAt,
    ) {}
}
