<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A password is about to be checked against the policy (the event never carries it).
 */
final class PasswordCheckingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ?Authenticatable $user,
    ) {}
}
