<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may see and change the two-factor enforcement policies:
 * `guardian.two-factor.manage`, globally.
 */
class TwoFactorPolicyPolicy extends Policy
{
    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.two-factor.manage', $subject, $self);
    }
}
