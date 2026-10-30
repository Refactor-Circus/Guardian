<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Data;

/**
 * Who the identity provider says the person is.
 *
 * `emailVerified` says whether the provider vouches for the email: OIDC only
 * when the id_token's `email_verified` claim is `true` (D-039). SAML and
 * Entra ID assert addresses on domains they own, so they leave it `true`.
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
        public bool $emailVerified = true,
    ) {}
}
