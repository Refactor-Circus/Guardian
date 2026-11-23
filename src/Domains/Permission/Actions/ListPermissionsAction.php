<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionsListedActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionsListingActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListPermissionsAction extends Action
{
    /**
     * Permissions by name, optionally those whose name contains `search`.
     *
     * @return LengthAwarePaginator<int, PermissionModel>
     */
    protected function handle(ListPermissionsData $filters = new ListPermissionsData): LengthAwarePaginator
    {
        PermissionsListingActionEvent::dispatch($filters);

        $query = PermissionModel::query();
        $search = $filters->search;

        if ($search !== null && $search !== '') {
            $query->whereLike('name', '%'.$search.'%');
        }

        $permissions = $query->orderBy('name')->orderBy('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        PermissionsListedActionEvent::dispatch($filters);

        return $permissions;
    }
}
