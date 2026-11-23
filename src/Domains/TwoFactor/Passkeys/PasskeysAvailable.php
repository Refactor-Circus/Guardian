<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Passkeys;

use Closure;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;

/**
 * Whether passkeys work here: web-auth/webauthn-lib is installed (D-010).
 * Without it every passkey endpoint answers 501 and the status says so.
 * Tests bind one with their own check.
 */
final class PasskeysAvailable
{
    /**
     * @param  (Closure(): bool)|null  $check
     */
    public function __construct(private readonly ?Closure $check = null) {}

    public function available(): bool
    {
        return $this->check !== null
            ? ($this->check)()
            : class_exists('Webauthn\\PublicKeyCredentialCreationOptions') && class_exists('Webauthn\\Denormalizer\\WebauthnSerializerFactory');
    }

    /**
     * @throws TwoFactorException 501, naming the package to install
     */
    public function ensure(): void
    {
        if (! $this->available()) {
            throw new TwoFactorException(__('guardian::guardian.passkeys_unavailable'), 'passkeys_unavailable', 501);
        }
    }
}
