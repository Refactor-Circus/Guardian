<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionDeletedActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionDeletingActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Keystone\Actions\Action;

final class DeletePermissionAction extends Action
{
    /**
     * Delete an app permission; every role stops granting it. Built-in
     * (system) permissions stay.
     */
    protected function handle(PermissionModel $permission): void
    {
        if ($permission->system) {
            throw ValidationException::withMessages(['permission' => __('guardian::guardian.system_permission')]);
        }

        PermissionDeletingActionEvent::dispatch($permission);

        DB::transaction(function () use ($permission): void {
            $permission->roles()->detach();
            $permission->delete();
        });

        app(GuardianPermissions::class)->forgetEveryone();

        PermissionDeletedActionEvent::dispatch($permission);
    }
}
