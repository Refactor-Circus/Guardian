<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;
use Workbench\App\Models\User;

/**
 * A role granting the given permissions (created when missing), assignable
 * anywhere unless a scope type says otherwise.
 *
 * @param  list<string>  $permissions
 */
function roleGranting(string $slug, array $permissions, string $scope = RoleModel::ANY, bool $super = false, ?Model $owner = null): RoleModel
{
    $role = RoleModel::query()->create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'scope' => $scope,
        'super' => $super,
        'owner_type' => $owner?->getMorphClass(),
        'owner_id' => $owner === null ? null : (string) $owner->getKey(),
    ]);

    $ids = array_map(
        fn (string $name): string => PermissionModel::query()->firstOrCreate(['name' => $name])->id,
        $permissions,
    );

    $role->permissions()->sync($ids);

    return $role;
}

/**
 * Write an assignment straight to the table, as another process would.
 */
function assignDirectly(User $user, RoleModel $role, ?Model $scope = null): RoleAssignmentModel
{
    return RoleAssignmentModel::query()->create([
        'role_id' => $role->id,
        'user_id' => $user->getKey(),
        'scope_type' => $scope?->getMorphClass(),
        'scope_id' => $scope === null ? null : (string) $scope->getKey(),
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function folder(array $attributes = []): FolderScope
{
    FolderScope::migrate();

    return FolderScope::query()->create($attributes + ['name' => 'Folder']);
}
