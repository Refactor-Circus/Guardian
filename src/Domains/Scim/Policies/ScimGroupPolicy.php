<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Policies\Policy;

/**
 * Groups a SCIM client provisioned need `guardian.scim.manage` in their
 * organization.
 */
class ScimGroupPolicy extends Policy
{
    public function view(?Model $user, ?ScimGroupModel $scimGroup = null): bool
    {
        return $this->permits($user, 'guardian.scim.manage', $scimGroup);
    }

    public function manage(?Model $user, ?ScimGroupModel $scimGroup = null): bool
    {
        return $this->permits($user, 'guardian.scim.manage', $scimGroup);
    }
}
