<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may see and change roles, and who holds them: `guardian.roles.view`,
 * `guardian.roles.manage` and `guardian.roles.assign`, in the scope a call
 * is about. It answers every `guardian.roles.*` ability the API and MCP
 * tools check, so swapping it in `guardian.policies` changes them all.
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

    public function assign(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.roles.assign', $subject, $self);
    }
}
