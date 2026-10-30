<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may see and configure single sign-on connections. Answers the
 * `guardian.sso.*` permissions in the connection's organization.
 */
class SsoConnectionPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.sso.view', $subject, $self);
    }

    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.sso.manage', $subject, $self);
    }
}
