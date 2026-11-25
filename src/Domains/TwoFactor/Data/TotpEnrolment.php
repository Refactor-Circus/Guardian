<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use SensitiveParameter;

/**
 * A TOTP enrolment just started: the unconfirmed method, and its secret and
 * `otpauth://` URI, answered this once.
 */
final readonly class TotpEnrolment
{
    public function __construct(
        public TwoFactorMethodModel $method,
        #[SensitiveParameter] public string $secret,
        #[SensitiveParameter] public string $otpauthUri,
    ) {}
}
