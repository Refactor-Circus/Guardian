<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Guardian\Domains\Role\Events\RoleDeletedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleDeletingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Keystone\Actions\Action;

final class DeleteRoleAction extends Action
{
    use GuardsEscalation;

    /**
     * Delete a role and every assignment of it. Built-in (system) roles stay,
     * and only a super-admin deletes a `super` role.
     */
    protected function handle(RoleModel $role, ?Model $actor = null): void
    {
        if ($role->system) {
            throw ValidationException::withMessages(['role' => __('guardian::guardian.cannot_delete_system')]);
        }

        $this->guardSuper($actor, $role->super, 'role');

        RoleDeletingActionEvent::dispatch($role);

        DB::transaction(function () use ($role): void {
            // Through the models, so each holder's grants are forgotten.
            $role->assignments()->get()->each(fn (RoleAssignmentModel $assignment): ?bool => $assignment->delete());
            $role->permissions()->detach();
            $role->delete();
        });

        app(GuardianPermissions::class)->forgetEveryone(names: false);

        RoleDeletedActionEvent::dispatch($role);
    }
}
