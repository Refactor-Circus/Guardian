<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * Another user's API tokens were listed by an admin.
 */
final class UserTokensListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Authenticatable $user,
    ) {}
}
