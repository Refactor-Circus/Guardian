<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may see and change roles: `guardian.roles.view` and
 * `guardian.roles.manage`, in the scope a call is about.
 */
class RolePolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.roles.view', $subject, $self);
    }

    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.roles.manage', $subject, $self);
    }
}
