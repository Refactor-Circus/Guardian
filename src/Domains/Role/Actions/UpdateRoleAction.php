<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Guardian\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Guardian\Domains\Role\Events\RoleUpdatedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleUpdatingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Support\RoleFinder;
use RefactorCircus\Keystone\Actions\Action;
use RefactorCircus\Keystone\Data\Missing;

final class UpdateRoleAction extends Action
{
    use GuardsEscalation;

    public function __construct(private readonly RoleFinder $roles) {}

    /**
     * Rename a role or replace its permissions. A given `permissions` list
     * replaces the old one; permissions it adds must be held by the actor
     * where the role lives, and only a super-admin changes a `super` role.
     */
    protected function handle(RoleModel $role, UpdateRoleData $data, ?Model $actor = null): RoleModel
    {
        $permissions = $data->permissions;

        $this->guardSuper($actor, $role->super, 'role');

        if (! $permissions instanceof Missing) {
            $current = $role->permissions()->pluck('name')->map(strval(...))->all();

            $this->guardEscalation($actor, array_values(array_diff($permissions, $current)), $this->roles->owner($role), 'permissions');
        }

        RoleUpdatingActionEvent::dispatch($role, $data);

        DB::transaction(function () use ($role, $data, $permissions): void {
            $role->update(Arr::only($data->toArray(), ['name', 'description']));

            if (! $permissions instanceof Missing) {
                $role->permissions()->sync(PermissionModel::query()->whereIn('name', $permissions)->pluck('id')->all());
            }
        });

        app(GuardianPermissions::class)->forgetEveryone(names: false);

        $role = $role->refresh()->load('permissions');

        RoleUpdatedActionEvent::dispatch($role);

        return $role;
    }
}
