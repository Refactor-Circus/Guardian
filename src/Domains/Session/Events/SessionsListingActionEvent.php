<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Session\Data\ListSessionsData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A user's sessions are about to be listed.
 */
final class SessionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Authenticatable $user,
        public ListSessionsData $filters,
    ) {}
}
