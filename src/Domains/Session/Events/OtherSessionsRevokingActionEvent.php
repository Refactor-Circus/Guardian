<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Session\Data\RevokeOtherSessionsData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A user is about to sign out everywhere else.
 */
final class OtherSessionsRevokingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Authenticatable $user,
        public RevokeOtherSessionsData $data,
    ) {}
}
