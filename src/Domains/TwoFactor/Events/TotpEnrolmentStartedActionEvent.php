<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A TOTP method was created, unconfirmed. It carries the method, never its secret.
 */
final class TotpEnrolmentStartedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TwoFactorMethodModel $method,
    ) {}
}
