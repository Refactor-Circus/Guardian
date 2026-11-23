<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Guardian\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssignedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssigningActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Support\RoleFinder;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Actions\Action;

final class AssignRoleAction extends Action
{
    use GuardsEscalation;

    public function __construct(
        private readonly Scopes $scopes,
        private readonly RoleFinder $roles,
        private readonly Users $users,
    ) {}

    /**
     * Give a user a role, globally or in a scope where the role may be
     * assigned. The actor must hold every permission of the role there, and
     * only a super-admin assigns a `super` role.
     */
    protected function handle(AssignRoleData $data, ?Model $actor = null): RoleAssignmentModel
    {
        $user = $this->users->find($data->user)
            ?? throw ValidationException::withMessages(['user' => __('guardian::guardian.unknown_user')]);
        $scope = $this->scopes->resolve($data->scopeType, $data->scopeId);
        $role = $this->roles->assignable($data->role, $scope)
            ?? throw ValidationException::withMessages(['role' => __('guardian::guardian.role_not_assignable')]);

        $this->guardSuper($actor, $role->super, 'role');
        $this->guardEscalation($actor, $role->permissions->map(fn (PermissionModel $permission): string => $permission->name)->all(), $scope, 'role');

        $attributes = [
            'role_id' => $role->id,
            'user_id' => $user->getKey(),
            'scope_type' => $scope?->getMorphClass(),
            'scope_id' => Scopes::key($scope),
        ];

        $taken = RoleAssignmentModel::query()
            ->where('role_id', $role->id)
            ->where('user_id', $user->getKey())
            ->where('scope_type', $attributes['scope_type'])
            ->where('scope_id', $attributes['scope_id'])
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['role' => __('guardian::guardian.role_already_assigned')]);
        }

        RoleAssigningActionEvent::dispatch($data);

        $assignment = DB::transaction(fn (): RoleAssignmentModel => RoleAssignmentModel::query()->create($attributes));

        $assignment->load('role');

        RoleAssignedActionEvent::dispatch($assignment);

        return $assignment;
    }
}
