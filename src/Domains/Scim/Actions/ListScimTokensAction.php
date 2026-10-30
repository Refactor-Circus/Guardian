<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Scim\Data\ListScimTokensData;
use RefactorCircus\Guardian\Domains\Scim\Events\ScimTokensListedActionEvent;
use RefactorCircus\Guardian\Domains\Scim\Events\ScimTokensListingActionEvent;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Keystone\Actions\Action;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class ListScimTokensAction extends Action
{
    /**
     * @return LengthAwarePaginator<int, ScimTokenModel>
     */
    protected function handle(OrganizationModel $organization, ListScimTokensData $filters = new ListScimTokensData): LengthAwarePaginator
    {
        ScimTokensListingActionEvent::dispatch($organization, $filters);

        $tokens = ScimTokenModel::query()
            ->where('organization_id', $organization->getKey())
            ->with(['organization', 'ssoConnection'])
            ->latest()
            ->latest('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);

        ScimTokensListedActionEvent::dispatch($organization, $filters);

        return $tokens;
    }
}
