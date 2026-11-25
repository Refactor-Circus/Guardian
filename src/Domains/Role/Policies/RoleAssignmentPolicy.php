<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may see and change who holds which role: `guardian.roles.view` and
 * `guardian.roles.assign`, in the scope a call is about.
 */
class RoleAssignmentPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.roles.view', $subject, $self);
    }

    public function assign(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.roles.assign', $subject, $self);
    }
}
