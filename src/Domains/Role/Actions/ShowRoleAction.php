<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Actions;

use RefactorCircus\Guardian\Domains\Role\Events\RoleShowingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleShownActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Keystone\Actions\Action;

final class ShowRoleAction extends Action
{
    /**
     * A role with the permissions it grants.
     */
    protected function handle(RoleModel $role): RoleModel
    {
        RoleShowingActionEvent::dispatch($role);

        $role->load('permissions');

        RoleShownActionEvent::dispatch($role);

        return $role;
    }
}
