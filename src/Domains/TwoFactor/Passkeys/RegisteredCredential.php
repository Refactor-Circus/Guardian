<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Passkeys;

/**
 * A credential a registration ceremony verified, as Guardian stores it: the
 * credential id in base64url, the COSE public key in base64.
 */
final readonly class RegisteredCredential
{
    /**
     * @param  list<string>  $transports
     */
    public function __construct(
        public string $credentialId,
        public string $publicKey,
        public int $signCount,
        public array $transports,
        public string $aaguid,
    ) {}
}
