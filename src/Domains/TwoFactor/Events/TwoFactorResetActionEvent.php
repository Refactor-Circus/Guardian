<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A user's two-factor was reset: every method, passkey and recovery code removed.
 */
final class TwoFactorResetActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
    ) {}
}
