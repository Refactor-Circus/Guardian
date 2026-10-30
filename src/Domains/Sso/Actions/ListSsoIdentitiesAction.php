<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Guardian\Domains\Sso\Concerns\ResolvesOrganizations;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoIdentitiesListedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoIdentitiesListingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Keystone\Actions\Action;
use RefactorCircus\Roster\Support\Users;

final class ListSsoIdentitiesAction extends Action
{
    use ResolvesOrganizations;

    public function __construct(private readonly Users $users) {}

    /**
     * @return LengthAwarePaginator<int, SsoIdentityModel>
     */
    protected function handle(ListSsoIdentitiesData $filters = new ListSsoIdentitiesData): LengthAwarePaginator
    {
        SsoIdentitiesListingActionEvent::dispatch($filters);

        $query = SsoIdentityModel::query()->with(['connection.organization', 'user']);

        if ($filters->user !== null) {
            $query->where('user_id', $this->users->resolve($filters->user)->getKey());
        }

        $organization = $this->organizationFrom($filters->organization);

        if ($organization !== null) {
            $query->whereHas('connection', fn (Builder $connection): Builder => $connection->where('organization_id', $organization->getKey()));
        }

        $identities = $query->latest()->latest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        SsoIdentitiesListedActionEvent::dispatch($filters);

        return $identities;
    }
}
