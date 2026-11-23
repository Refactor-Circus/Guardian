<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission;

use RefactorCircus\Guardian\Domains\Permission\Console\Commands\SyncPermissionsCommand;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * Permissions: one row per Keystone ability (kept in step by
 * `guardian:sync-permissions`) plus a host's own. Needs nothing from Roster.
 */
class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);
        }
    }
}
