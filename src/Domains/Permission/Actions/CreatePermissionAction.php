<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionCreatedActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionCreatingActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Keystone\Actions\Action;

final class CreatePermissionAction extends Action
{
    /**
     * Declare an app permission, e.g. `invoices.edit`, for roles to grant.
     * The permission model's events forget every remembered answer.
     */
    protected function handle(CreatePermissionData $data): PermissionModel
    {
        PermissionCreatingActionEvent::dispatch($data);

        $permission = DB::transaction(fn (): PermissionModel => PermissionModel::query()->create([
            'name' => $data->name,
            'description' => $data->description,
            'system' => false,
        ]));

        PermissionCreatedActionEvent::dispatch($permission);

        return $permission;
    }
}
