<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may read the security log: `guardian.security.view` (everyone their
 * own events), and `guardian.security.manage` for what later domains let
 * admins change.
 */
class SecurityEventPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.security.view', $subject, $self);
    }

    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.security.manage', $subject, $self);
    }
}
