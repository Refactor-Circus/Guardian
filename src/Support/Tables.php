<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Support;

use RefactorCircus\Keystone\Database\Tables as KeystoneTables;

/**
 * The table each of Guardian's records lives in, from the `guardian.tables`
 * config key. Models, migrations, validation rules and joins all ask here,
 * so renaming a table is one config change made before the migrations run.
 *
 * A published config that lists only some tables keeps the defaults for the
 * rest.
 */
final class Tables extends KeystoneTables
{
    protected const string CONFIG = 'guardian.tables';

    /**
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        'sso_connections' => 'guardian_sso_connections',
        'sso_identities' => 'guardian_sso_identities',
        'scim_tokens' => 'guardian_scim_tokens',
        'scim_users' => 'guardian_scim_users',
        'scim_groups' => 'guardian_scim_groups',
        'permissions' => 'guardian_permissions',
        'roles' => 'guardian_roles',
        'permission_role' => 'guardian_permission_role',
        'role_assignments' => 'guardian_role_assignments',
        'security_events' => 'guardian_security_events',
        'two_factor_methods' => 'guardian_two_factor_methods',
        'recovery_codes' => 'guardian_recovery_codes',
        'passkeys' => 'guardian_passkeys',
        'one_time_codes' => 'guardian_one_time_codes',
        'two_factor_policies' => 'guardian_two_factor_policies',
        'devices' => 'guardian_devices',
        'sessions' => 'guardian_sessions',
    ];
}
