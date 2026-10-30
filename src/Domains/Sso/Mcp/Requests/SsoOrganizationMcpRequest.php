<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Requests;

use RefactorCircus\Guardian\Mcp\Requests\Request;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * A tool call about one Roster organization, named by its `organization` slug.
 */
abstract class SsoOrganizationMcpRequest extends Request
{
    private ?OrganizationModel $resolvedOrganization = null;

    /**
     * Checked in the organization the call names.
     */
    protected function scope(): OrganizationModel|TeamModel|null
    {
        return $this->organization();
    }

    /**
     * The organization named by the `organization` slug argument.
     */
    protected function organization(): OrganizationModel
    {
        return $this->resolvedOrganization ??= OrganizationModel::query()
            ->where('slug', $this->get('organization'))
            ->firstOrFail();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function organizationRules(): array
    {
        return ['organization' => ['required', 'string']];
    }

    /**
     * Every organization tool names its organization.
     */
    protected function rules(): array
    {
        return $this->organizationRules();
    }
}
