<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * A linked sign-in follows its connection: `guardian.sso.view` to see it,
 * `guardian.sso.manage` to unlink it.
 */
class SsoIdentityPolicy extends Policy
{
    public function view(?Model $user, ?SsoIdentityModel $identity = null): bool
    {
        return $this->permits($user, 'guardian.sso.view', $identity?->connection);
    }

    public function manage(?Model $user, ?SsoIdentityModel $identity = null): bool
    {
        return $this->permits($user, 'guardian.sso.manage', $identity?->connection);
    }
}
