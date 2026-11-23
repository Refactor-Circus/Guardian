<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A TOTP method is about to be confirmed.
 */
final class TotpConfirmingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TwoFactorMethodModel $method,
    ) {}
}
