<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A session is about to be revoked.
 */
final class SessionRevokingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SessionModel $session,
    ) {}
}
