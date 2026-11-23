<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Password\Data\PasswordCheck;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A password was checked against the policy (the event never carries it).
 */
final class PasswordCheckedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ?Authenticatable $user,
        public PasswordCheck $result,
    ) {}
}
