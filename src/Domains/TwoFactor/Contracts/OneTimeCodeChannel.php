<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use SensitiveParameter;

/**
 * Delivers email and SMS one-time codes (criterion 29, D-011). Guardian
 * binds `MailOneTimeCodeChannel` (email only); a host binds its own to send
 * SMS, or to send email its own way.
 */
interface OneTimeCodeChannel
{
    /**
     * Whether codes of this method type (`email`, `sms`) can be sent.
     */
    public function supports(string $type): bool;

    /**
     * Send a code to a destination (an email address, a phone number).
     */
    public function send(Authenticatable $user, string $type, string $destination, #[SensitiveParameter] string $code): void;
}
