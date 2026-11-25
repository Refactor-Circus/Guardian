<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Guardian\Domains\Role\Events\RoleRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Actions\Action;

final class RevokeRoleAction extends Action
{
    use GuardsEscalation;

    public function __construct(private readonly Scopes $scopes) {}

    /**
     * Take a role away. Like granting, revoking needs the actor to hold the
     * role's permissions in that scope, and only a super-admin takes a
     * `super` role away.
     */
    protected function handle(RoleAssignmentModel $assignment, ?Model $actor = null): void
    {
        $assignment->loadMissing('role.permissions');

        /** @var RoleModel $role */
        $role = $assignment->role;

        $this->guardSuper($actor, $role->super, 'role');
        $this->guardEscalation(
            $actor,
            $role->permissions->map(fn (PermissionModel $permission): string => $permission->name)->all(),
            $this->scopes->find($assignment->scope_type, $assignment->scope_id),
            'role',
        );

        RoleRevokingActionEvent::dispatch($assignment);

        // Through the model, so its holder's grants are forgotten.
        DB::transaction(fn (): ?bool => $assignment->delete());

        RoleRevokedActionEvent::dispatch($assignment);
    }
}
