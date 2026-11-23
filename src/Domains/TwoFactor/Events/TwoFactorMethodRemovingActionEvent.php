<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A two-factor method is about to be removed.
 */
final class TwoFactorMethodRemovingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TwoFactorMethodModel $method,
    ) {}
}
