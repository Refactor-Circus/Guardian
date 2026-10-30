<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Sso\Concerns\ResolvesOrganizations;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionsListedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionsListingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListSsoConnectionsAction extends Action
{
    use ResolvesOrganizations;

    /**
     * @return LengthAwarePaginator<int, SsoConnectionModel>
     */
    protected function handle(ListSsoConnectionsData $filters = new ListSsoConnectionsData): LengthAwarePaginator
    {
        SsoConnectionsListingActionEvent::dispatch($filters);

        $query = SsoConnectionModel::query()->with('organization')->withCount('identities');
        $organization = $this->organizationFrom($filters->organization);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        $connections = $query->orderBy('name')->orderBy('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        SsoConnectionsListedActionEvent::dispatch($filters);

        return $connections;
    }
}
