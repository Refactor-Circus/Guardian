<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Accounts a SCIM client provisioned need `guardian.scim.manage` in their
 * organization.
 */
class ScimUserPolicy extends Policy
{
    public function view(?Model $user, ?ScimUserModel $scimUser = null): bool
    {
        return $this->permits($user, 'guardian.scim.manage', $scimUser);
    }

    public function manage(?Model $user, ?ScimUserModel $scimUser = null): bool
    {
        return $this->permits($user, 'guardian.scim.manage', $scimUser);
    }
}
