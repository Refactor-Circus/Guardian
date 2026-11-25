<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Session\Data\SessionsRevoked;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * Every session of a user was revoked by an admin.
 */
final class UserSessionsRevokedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Authenticatable $user,
        public SessionsRevoked $result,
    ) {}
}
