<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission;

use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\Permission\Console\Commands\SyncPermissionsCommand;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionDeletedEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionSavedEvent;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssignmentDeletedEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssignmentSavedEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleDeletedEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleSavedEvent;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * Permissions: one row per Keystone ability (kept in step by
 * `guardian:sync-permissions`) plus a host's own. Needs nothing from Roster.
 */
class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->forgetOnWrites();

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);
        }
    }

    /**
     * Guardian's role, assignment and permission models forget what they
     * change (criterion 13): an assignment its user's grants (before and
     * after), a role everyone's grants, a permission the names too. Pivot
     * changes fire no model events; the actions that make them forget.
     */
    private function forgetOnWrites(): void
    {
        $forgetAssignment = function (RoleAssignmentSavedEvent|RoleAssignmentDeletedEvent $event): void {
            $permissions = $this->app->make(GuardianPermissions::class);
            $assignment = $event->roleAssignment;

            $permissions->forgetKey($assignment->user_id);
            $permissions->forgetKey($assignment->getOriginal('user_id'));
        };

        Event::listen(RoleAssignmentSavedEvent::class, $forgetAssignment);
        Event::listen(RoleAssignmentDeletedEvent::class, $forgetAssignment);

        $forgetRoles = function (): void {
            $this->app->make(GuardianPermissions::class)->forgetEveryone(names: false);
        };

        Event::listen(RoleSavedEvent::class, $forgetRoles);
        Event::listen(RoleDeletedEvent::class, $forgetRoles);

        $forgetNames = function (): void {
            $this->app->make(GuardianPermissions::class)->forgetEveryone(names: true);
        };

        Event::listen(PermissionSavedEvent::class, $forgetNames);
        Event::listen(PermissionDeletedEvent::class, $forgetNames);
    }
}
