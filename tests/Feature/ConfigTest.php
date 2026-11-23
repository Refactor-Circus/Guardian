<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Guardian\Domains\LoginProtection\Policies\IpRulePolicy;
use RefactorCircus\Guardian\Domains\Password\Models\PasswordHistoryModel;
use RefactorCircus\Guardian\Domains\Password\Policies\PasswordHistoryPolicy;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Permission\Policies\PermissionPolicy;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Policies\RoleAssignmentPolicy;
use RefactorCircus\Guardian\Domains\Role\Policies\RolePolicy;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimGroupPolicy;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimTokenPolicy;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimUserPolicy;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Policies\SecurityEventPolicy;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Session\Policies\SessionPolicy;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoIdentityPolicy;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\OneTimeCodeModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\RecoveryCodeModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Policies\TwoFactorMethodPolicy;
use RefactorCircus\Guardian\Domains\TwoFactor\Policies\TwoFactorPolicyPolicy;
use Workbench\App\Models\User;

/*
 * config/guardian.php (criterion 6). Roster's `roster.sso` / `roster.scim`
 * defaults are pinned to the Roster commit the move is taken from, because
 * Roster drops them once it hands SSO and SCIM over; the shared keys are
 * Keen's literal defaults at Keen's pinned commit, renamed for Guardian.
 */

/** Roster `auth-extension-seams`, the source of the moved SSO and SCIM code. */
const CONFIG_ROSTER_SHA = '6b1833dae7ed21ba352612a13693d662bc7354cc';

/** Keen, whose `routes`, `mcp`, `cortex`, `policies` and `tables` shapes Guardian copies. */
const CONFIG_KEEN_SHA = '94a01fafb53818e6ea294fb55657221742eea0b4';

/** `roster.sso` at CONFIG_ROSTER_SHA. */
const CONFIG_ROSTER_SSO = [
    'redirect' => '/',
    'discovery_cache_seconds' => 3600,
    'prefix' => 'roster/sso',
    'middleware' => ['web'],
];

/** `roster.scim` at CONFIG_ROSTER_SHA. */
const CONFIG_ROSTER_SCIM = [
    'enabled' => true,
    'prefix' => 'scim/v2',
    'middleware' => ['api', 'throttle:roster'],
    'default_count' => 100,
    'max_count' => 500,
    'bulk' => [
        'max_operations' => 100,
        'max_payload_bytes' => 1048576,
    ],
];

/**
 * The shipped file, as an application that has not published it reads it.
 *
 * @return array<string, mixed>
 */
function shippedGuardianConfig(): array
{
    /** @var array<string, mixed> */
    return require dirname(__DIR__, 2).'/config/guardian.php';
}

/**
 * Every string value in the file, at any depth.
 *
 * @param  array<array-key, mixed>  $config
 * @return array<int, string>
 */
function guardianConfigStrings(array $config): array
{
    $strings = [];

    array_walk_recursive($config, function (mixed $value) use (&$strings): void {
        if (is_string($value)) {
            $strings[] = $value;
        }
    });

    return $strings;
}

it('keeps Roster\'s SSO defaults', function (): void {
    expect(shippedGuardianConfig()['sso'])->toBe(CONFIG_ROSTER_SSO)
        ->and(config('guardian.sso'))->toBe(CONFIG_ROSTER_SSO);
});

it('keeps Roster\'s SCIM defaults, throttled by Guardian\'s limiter', function (): void {
    $scim = shippedGuardianConfig()['scim'];

    expect($scim['middleware'])->toBe(['api', 'throttle:guardian'])
        ->and($scim['audit_context_packages'])->toBe(['guardian', 'roster'])
        ->and(array_diff_key($scim, ['middleware' => true, 'audit_context_packages' => true]))
        ->toBe(array_diff_key(CONFIG_ROSTER_SCIM, ['middleware' => true]));
});

it('pins Roster\'s SSO and SCIM defaults as Roster shipped them at the pinned SHA', function (): void {
    // config/roster.php at CONFIG_ROSTER_SHA, captured once: the Roster
    // installed here may already have dropped both sections.
    $roster = require dirname(__DIR__).'/Fixtures/roster-6b1833d/roster.php';

    expect(CONFIG_ROSTER_SHA)->toBe(json_decode((string) file_get_contents(dirname(__DIR__).'/moved-tests.json'), true)['sha'])
        ->and($roster['sso'])->toBe(CONFIG_ROSTER_SSO, 'roster.sso differs from '.CONFIG_ROSTER_SHA)
        ->and($roster['scim'])->toBe(CONFIG_ROSTER_SCIM, 'roster.scim differs from '.CONFIG_ROSTER_SHA);
});

it('keeps the API routes off by default, behind Guardian\'s limiter', function (): void {
    expect(shippedGuardianConfig()['routes'])->toBe([
        'enabled' => false,
        'prefix' => 'guardian',
        'middleware' => ['api', 'throttle:guardian'],
    ]);
});

it('has the authorization, rate limit, audit and rollback defaults', function (): void {
    $config = shippedGuardianConfig();

    expect($config['authorization'])->toBe(['enabled' => true, 'bind' => true])
        ->and($config['super_admins'])->toBe([])
        ->and($config['permission_cache'])->toBe(['ttl' => 0, 'store' => null])
        ->and($config['rate_limit'])->toBe(['per_minute' => 120])
        ->and($config['audit'])->toBe(['enabled' => true])
        ->and($config['migrations'])->toBe(['force_rollback' => false])
        ->and(config('guardian.migrations.force_rollback'))->toBeFalse();
});

it('reads the rollback override from GUARDIAN_FORCE_ROLLBACK', function (): void {
    expect((string) file_get_contents(dirname(__DIR__, 2).'/config/guardian.php'))
        ->toContain("'force_rollback' => (bool) env('GUARDIAN_FORCE_ROLLBACK', false)");
});

it('serves MCP and Cortex with Keen\'s defaults, under Guardian\'s names', function (): void {
    $config = shippedGuardianConfig();

    expect($config['mcp'])->toBe([
        'web' => [
            'enabled' => false,
            'route' => 'mcp/guardian',
            'middleware' => ['api'],
        ],
        'local' => [
            'enabled' => false,
            'handle' => 'guardian',
        ],
    ], 'Keen at '.CONFIG_KEEN_SHA)
        ->and($config['cortex'])->toBe([
            'enabled' => true,
            'server' => 'guardian',
            'tools' => null,
        ], 'Keen at '.CONFIG_KEEN_SHA);
});

it('maps every model to its policy and every table to its default name', function (): void {
    $config = shippedGuardianConfig();

    expect($config['policies'])->toBe([
        SsoConnectionModel::class => SsoConnectionPolicy::class,
        SsoIdentityModel::class => SsoIdentityPolicy::class,
        ScimTokenModel::class => ScimTokenPolicy::class,
        ScimUserModel::class => ScimUserPolicy::class,
        ScimGroupModel::class => ScimGroupPolicy::class,
        RoleModel::class => RolePolicy::class,
        RoleAssignmentModel::class => RoleAssignmentPolicy::class,
        PermissionModel::class => PermissionPolicy::class,
        SecurityEventModel::class => SecurityEventPolicy::class,
        TwoFactorMethodModel::class => TwoFactorMethodPolicy::class,
        RecoveryCodeModel::class => TwoFactorMethodPolicy::class,
        OneTimeCodeModel::class => TwoFactorMethodPolicy::class,
        PasskeyModel::class => TwoFactorMethodPolicy::class,
        TwoFactorPolicyModel::class => TwoFactorPolicyPolicy::class,
        DeviceModel::class => SessionPolicy::class,
        SessionModel::class => SessionPolicy::class,
        PasswordHistoryModel::class => PasswordHistoryPolicy::class,
        IpRuleModel::class => IpRulePolicy::class,
    ])->and($config['tables'])->toBe([
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
        'password_histories' => 'guardian_password_histories',
        'ip_rules' => 'guardian_ip_rules',
    ]);
});

it('has the users and roles defaults', function (): void {
    $config = shippedGuardianConfig();

    expect($config['users'])->toBe(['model' => null, 'key_type' => 'int'])
        ->and($config['roles'])->toBe(['built_in' => [
            'super-admin' => ['name' => 'Super admin', 'scope' => 'global', 'super' => true],
            'admin' => ['name' => 'Admin', 'scope' => 'any', 'super' => false],
            'member' => ['name' => 'Member', 'scope' => 'any', 'super' => false],
        ]]);
});

it('has the adoption defaults: Roster v0.1\'s table names and model classes', function (): void {
    expect(shippedGuardianConfig()['adopt'])->toBe([
        'roster_tables' => [
            'permissions' => 'roster_permissions',
            'roles' => 'roster_roles',
            'permission_role' => 'roster_permission_role',
            'role_assignments' => 'roster_role_assignments',
        ],
        'scope_types' => [
            'organization' => 'RefactorCircus\\Roster\\Domains\\Organization\\Models\\OrganizationModel',
            'team' => 'RefactorCircus\\Roster\\Domains\\Team\\Models\\TeamModel',
        ],
    ]);
});

it('has the two-factor defaults', function (): void {
    expect(shippedGuardianConfig()['two_factor'])->toBe([
        'required' => false,
        'issuer' => null,
        'one_time_codes' => ['ttl_minutes' => 10],
        'passkeys' => ['rp_id' => null, 'rp_name' => null, 'origins' => null],
    ]);
});

it('has the session and device defaults', function (): void {
    expect(shippedGuardianConfig()['sessions'])->toBe(['touch_seconds' => 60])
        ->and(shippedGuardianConfig()['devices'])->toBe(['notify' => true, 'notify_via' => ['mail']]);
});

it('has the password defaults', function (): void {
    expect(shippedGuardianConfig()['passwords'])->toBe([
        'column' => 'password',
        'min_length' => 12,
        'max_length' => 128,
        'mixed_case' => false,
        'numbers' => false,
        'symbols' => false,
        'letters' => false,
        'uncompromised' => true,
        'uncompromised_threshold' => 0,
        'history' => 5,
        'max_age_days' => null,
        'check_per_minute' => 10,
    ]);
});

it('has the token defaults: no driver', function (): void {
    expect(shippedGuardianConfig()['tokens'])->toBe(['driver' => null, 'abilities' => ['*'], 'max_lifetime_days' => 365]);
});

it('has the login protection and IP rule defaults', function (): void {
    expect(shippedGuardianConfig()['login'])->toBe(['identifier_field' => 'email', 'max_attempts' => 5, 'decay_minutes' => 15, 'ip_max_attempts' => 50])
        ->and(shippedGuardianConfig()['ip_rules'])->toBe(['mode' => 'denylist']);
});

it('has the security log defaults', function (): void {
    expect(shippedGuardianConfig()['security_log'])->toBe([
        'enabled' => true,
        'retention_days' => 90,
        'identifier_keys' => ['email', 'username'],
    ]);
});

it('has exactly the documented sections, each under a banner', function (): void {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/config/guardian.php');

    expect(array_keys(shippedGuardianConfig()))->toBe([
        'sso', 'scim', 'routes', 'authorization', 'super_admins', 'permission_cache', 'rate_limit', 'audit', 'users', 'roles', 'adopt', 'security_log', 'two_factor', 'sessions', 'devices', 'passwords', 'tokens', 'login', 'ip_rules', 'policies', 'tables', 'mcp', 'cortex', 'migrations',
    ])->and(substr_count($source, '|--------------------------------------------------------------------------'))
        ->toBe(2 * count(shippedGuardianConfig()));
});

it('never names a roster.* config key', function (): void {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/config/guardian.php');

    expect(array_filter(guardianConfigStrings(shippedGuardianConfig()), fn (string $value): bool => str_starts_with($value, 'roster.')))->toBe([])
        ->and($source)->not->toContain("'roster.sso")
        ->and($source)->not->toContain("'roster.scim")
        ->and($source)->not->toContain('config(');
});

describe('the guardian rate limiter', function (): void {
    beforeEach(function (): void {
        Route::middleware('throttle:guardian')->get('guardian-throttle-probe', fn (): string => 'ok');
    });

    it('answers the 121st request in a minute with a 429', function (): void {
        foreach (range(1, 120) as $request) {
            $this->get('guardian-throttle-probe')->assertOk();
        }

        $this->get('guardian-throttle-probe')->assertTooManyRequests();
    });

    it('counts each signed-in user apart from guests', function (): void {
        foreach (range(1, 120) as $request) {
            $this->get('guardian-throttle-probe')->assertOk();
        }

        $user = User::query()->forceCreate(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']);

        $this->actingAs($user)->get('guardian-throttle-probe')->assertOk();
    });

    it('is off when per_minute is null', function (): void {
        config()->set('guardian.rate_limit.per_minute', null);

        foreach (range(1, 125) as $request) {
            $this->get('guardian-throttle-probe')->assertOk();
        }
    });
});
