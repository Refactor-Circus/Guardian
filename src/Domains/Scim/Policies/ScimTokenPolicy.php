<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Who may issue and revoke SCIM tokens. Answers the `guardian.scim.manage`
 * permission in the token's organization.
 */
class ScimTokenPolicy extends Policy
{
    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'guardian.scim.manage', $subject, $self);
    }
}
