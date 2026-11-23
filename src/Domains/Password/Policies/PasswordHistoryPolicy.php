<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Password history is never read over the API; `guardian.security.manage`
 * stands for anything a host lets admins do with it.
 */
class PasswordHistoryPolicy extends Policy
{
    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.security.manage', $subject, $self);
    }
}
