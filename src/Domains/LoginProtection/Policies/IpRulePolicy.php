<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may manage IP rules and clear lockouts: `guardian.security.manage`,
 * globally.
 */
class IpRulePolicy extends Policy
{
    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.security.manage', $subject, $self);
    }
}
