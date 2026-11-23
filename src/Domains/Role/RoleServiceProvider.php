<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role;

use RefactorCircus\Guardian\Domains\Role\Support\GuardianAbilities;
use RefactorCircus\Keystone\Authorization\Abilities;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * Roles and who holds them: global, `any`-scope and scope-typed roles,
 * custom roles owned by one scope, and the built-in roles from
 * `guardian.roles.built_in`. Needs nothing from Roster.
 *
 * Defines Guardian's abilities in Keystone's `Abilities` (criterion 8), so
 * every role store, Guardian's included, can grant them.
 */
class RoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        GuardianAbilities::define($this->app->make(Abilities::class));
    }
}
