<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\Role\Services\GuardianRoles;
use RefactorCircus\Guardian\Domains\Role\Support\BuiltInRoles;
use RefactorCircus\Guardian\Support\Users;

/**
 * Moved from Roster (criterion 19): assigns the built-in `super-admin` role
 * globally to an existing user, in Guardian's role tables.
 */
final class GrantSuperAdminCommand extends Command
{
    protected $signature = 'guardian:grant-super-admin {email : The email of an existing user}';

    protected $description = 'Give an existing user the super-admin role';

    public function handle(Users $users, GuardianRoles $roles): int
    {
        $email = $this->argument('email');
        $email = is_string($email) ? $email : '';
        $user = $users->findByEmail($email);

        if (! $user instanceof Authenticatable) {
            $this->components->error("No user has the email [{$email}].");

            return self::FAILURE;
        }

        BuiltInRoles::sync();

        if (! $roles->assign($user, 'super-admin')) {
            $this->components->error('There is no global super-admin role: check guardian.roles.built_in.');

            return self::FAILURE;
        }

        $this->components->info("[{$email}] is now a super-admin.");

        return self::SUCCESS;
    }
}
