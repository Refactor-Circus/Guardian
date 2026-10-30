<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Data;

/**
 * Who the identity provider says the person is.
 */
final readonly class IdentityClaims
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $subject,
        public ?string $email,
        public ?string $name,
        public array $raw = [],
    ) {}
}
