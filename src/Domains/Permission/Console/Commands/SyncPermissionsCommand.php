<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Console\Commands;

use Illuminate\Console\Command;
use RefactorCircus\Guardian\Domains\Role\Support\BuiltInRoles;

/**
 * Adds the permissions Keystone's abilities need and the missing built-in
 * roles (criterion 7). Run after installing or upgrading a package that
 * defines abilities; the role tables migration runs it too.
 */
final class SyncPermissionsCommand extends Command
{
    protected $signature = 'guardian:sync-permissions';

    protected $description = 'Add the permissions every installed package defines and the missing built-in roles';

    public function handle(): int
    {
        foreach (BuiltInRoles::sync() as $name) {
            $this->components->warn("System permission [{$name}] is no longer defined by any package. It was left in place; delete it once nothing uses it.");
        }

        $this->components->info('Guardian permissions and built-in roles are up to date.');

        return self::SUCCESS;
    }
}
