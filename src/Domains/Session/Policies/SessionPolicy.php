<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may see and revoke sessions and devices: everyone their own, and
 * `guardian.sessions.manage` for another user's.
 */
class SessionPolicy extends Policy
{
    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.sessions.manage', $subject, $self);
    }
}
