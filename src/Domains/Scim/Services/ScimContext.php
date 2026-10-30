<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Services;

use RefactorCircus\Guardian\Domains\Scim\Exceptions\ScimException;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * The SCIM token and organization the current request acts for. Bound per
 * request.
 */
final class ScimContext
{
    public ?ScimTokenModel $token = null;

    /**
     * Groups per SCIM user id, resolved for a whole page at once.
     *
     * @var array<string, array<int, ScimGroupModel>>
     */
    public array $groups = [];

    /**
     * Members per SCIM group id, resolved for a whole page at once.
     *
     * @var array<string, array<int, ScimUserModel>>
     */
    public array $members = [];

    public function organization(): OrganizationModel
    {
        $organization = $this->token?->organization;

        if (! $organization instanceof OrganizationModel) {
            throw new ScimException(401, 'Authentication required.');
        }

        return $organization;
    }

    public function active(): bool
    {
        return $this->token !== null;
    }
}
