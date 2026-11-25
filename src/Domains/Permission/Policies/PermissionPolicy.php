<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may see and change permissions: `guardian.roles.view` and
 * `guardian.roles.manage`.
 */
class PermissionPolicy extends Policy
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
