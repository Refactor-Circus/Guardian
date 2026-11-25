<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionUpdatedActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionUpdatingActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Keystone\Actions\Action;

final class UpdatePermissionAction extends Action
{
    /**
     * Rename a permission or change its description. A built-in (system)
     * permission keeps its name: code checks it. Roles keep granting a
     * renamed permission.
     */
    protected function handle(PermissionModel $permission, UpdatePermissionData $data): PermissionModel
    {
        $name = $data->name;

        if (is_string($name) && $name !== $permission->name) {
            if ($permission->system) {
                throw ValidationException::withMessages(['name' => __('guardian::guardian.system_permission')]);
            }

            if (PermissionModel::query()->where('name', $name)->exists()) {
                throw ValidationException::withMessages(['name' => __('guardian::guardian.permission_name_taken')]);
            }
        }

        PermissionUpdatingActionEvent::dispatch($permission, $data);

        DB::transaction(fn (): bool => $permission->update($data->toArray()));

        $permission = $permission->refresh();

        PermissionUpdatedActionEvent::dispatch($permission);

        return $permission;
    }
}
