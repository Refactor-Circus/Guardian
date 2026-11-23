<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Guardian\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Guardian\Domains\Role\Events\RoleCreatedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleCreatingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Support\RoleFinder;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Actions\Action;

final class CreateRoleAction extends Action
{
    use GuardsEscalation;

    public function __construct(
        private readonly Scopes $scopes,
        private readonly RoleFinder $roles,
    ) {}

    /**
     * Create a role. With a scope it is that scope's own (assignable `any`
     * where it lives, or with the scope's own type); without, it is shared.
     * The actor may only grant permissions they hold there, and only a
     * super-admin creates a `super` role.
     */
    protected function handle(CreateRoleData $data, ?Model $actor = null): RoleModel
    {
        $owner = $this->scopes->resolve($data->scopeType, $data->scopeId);
        $scope = $this->roles->assignableIn($data->scope);
        $slug = $data->slug !== null && $data->slug !== '' ? $data->slug : Str::slug($data->name);
        $permissions = array_values($data->permissions);

        if ($owner !== null && $scope === RoleModel::GLOBAL) {
            throw ValidationException::withMessages(['scope' => __('guardian::guardian.global_role_shared')]);
        }

        if ($owner !== null && ! in_array($scope, [RoleModel::ANY, $owner->getMorphClass()], true)) {
            throw ValidationException::withMessages(['scope' => __('guardian::guardian.role_scope_owner')]);
        }

        $taken = RoleModel::query()
            ->where('slug', $slug)
            ->where('owner_type', $owner?->getMorphClass())
            ->where('owner_id', Scopes::key($owner))
            ->exists();

        if ($taken || $slug === '') {
            throw ValidationException::withMessages(['slug' => __('guardian::guardian.role_slug_taken')]);
        }

        $this->guardSuper($actor, $data->super, 'super');
        $this->guardEscalation($actor, $permissions, $owner, 'permissions');

        RoleCreatingActionEvent::dispatch($data);

        $role = DB::transaction(function () use ($data, $scope, $owner, $slug, $permissions): RoleModel {
            $role = RoleModel::query()->create([
                'name' => $data->name,
                'slug' => $slug,
                'scope' => $scope,
                'owner_type' => $owner?->getMorphClass(),
                'owner_id' => Scopes::key($owner),
                'description' => $data->description,
                'super' => $data->super,
            ]);

            $role->permissions()->sync(PermissionModel::query()->whereIn('name', $permissions)->pluck('id')->all());

            return $role;
        });

        app(GuardianPermissions::class)->forgetEveryone(names: false);

        $role->load('permissions');

        RoleCreatedActionEvent::dispatch($role);

        return $role;
    }
}
