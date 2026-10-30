<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use RefactorCircus\Guardian\Http\Requests\Request;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * A request about one Roster organization, named by slug in the route.
 */
abstract class SsoOrganizationRequest extends Request
{
    private ?OrganizationModel $resolvedOrganization = null;

    /**
     * Checked in the organization the request names.
     */
    protected function scope(): OrganizationModel|TeamModel|null
    {
        return $this->organization();
    }

    /**
     * The organization named in the route, by slug.
     */
    protected function organization(): OrganizationModel
    {
        return $this->resolvedOrganization ??= OrganizationModel::query()
            ->where('slug', $this->route('organization'))
            ->firstOrFail();
    }
}
