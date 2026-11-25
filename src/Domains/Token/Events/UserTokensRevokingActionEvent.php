<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * Every API token of a user is about to be revoked by an admin.
 */
final class UserTokensRevokingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Authenticatable $user,
    ) {}
}
