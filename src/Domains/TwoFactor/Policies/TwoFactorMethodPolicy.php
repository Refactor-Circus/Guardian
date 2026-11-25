<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may manage two-factor: everyone their own factors, and
 * `guardian.two-factor.manage` for another user's (an admin reset) and the
 * enforcement policies.
 */
class TwoFactorMethodPolicy extends Policy
{
    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.two-factor.manage', $subject, $self);
    }
}
