<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Guardian\Domains\Role\Data\ListRolesData;
use RefactorCircus\Guardian\Domains\Role\Events\RolesListedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RolesListingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Support\RoleFinder;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Actions\Action;

final class ListRolesAction extends Action
{
    public function __construct(
        private readonly Scopes $scopes,
        private readonly RoleFinder $roles,
    ) {}

    /**
     * Roles by scope and name. With a scope, the roles usable there: the
     * shared ones plus its own. Without, the shared ones.
     *
     * @return LengthAwarePaginator<int, RoleModel>
     */
    protected function handle(ListRolesData $filters = new ListRolesData): LengthAwarePaginator
    {
        RolesListingActionEvent::dispatch($filters);

        $scope = $this->scopes->resolve($filters->scopeType, $filters->scopeId);

        $query = RoleModel::query()
            ->with('permissions')
            ->where(fn (Builder $owner): Builder => $scope === null
                ? $owner->whereNull('owner_type')
                : $owner->whereNull('owner_type')->orWhere(fn (Builder $own): Builder => $own
                    ->where('owner_type', $scope->getMorphClass())
                    ->where('owner_id', Scopes::key($scope))));

        if ($filters->scope !== null && $filters->scope !== '') {
            $query->where('scope', $this->roles->assignableIn($filters->scope));
        }

        $roles = $query->orderBy('scope')->orderBy('name')->orderBy('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        RolesListedActionEvent::dispatch($filters);

        return $roles;
    }
}
