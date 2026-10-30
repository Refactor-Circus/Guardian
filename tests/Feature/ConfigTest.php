<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimGroupPolicy;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimTokenPolicy;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimUserPolicy;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoIdentityPolicy;
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

it('matches the installed Roster while it still ships SSO and SCIM', function (): void {
    $roster = require dirname(__DIR__, 2).'/vendor/refactor-circus/roster/config/roster.php';

    if (! isset($roster['sso'], $roster['scim'])) {
        $this->markTestSkipped('The installed Roster no longer ships SSO and SCIM; the pinned values at '.CONFIG_ROSTER_SHA.' stand.');
    }

    expect($roster['sso'])->toBe(CONFIG_ROSTER_SSO, 'roster.sso differs from '.CONFIG_ROSTER_SHA)
        ->and($roster['scim'])->toBe(CONFIG_ROSTER_SCIM, 'roster.scim differs from '.CONFIG_ROSTER_SHA);
});

it('keeps the API routes off by default, behind Guardian\'s limiter', function (): void {
    expect(shippedGuardianConfig()['routes'])->toBe([
        'enabled' => false,
        'prefix' => 'guardian',
        'middleware' => ['api', 'throttle:guardian'],
    ]);
});

it('has the rate limit, audit and rollback defaults', function (): void {
    $config = shippedGuardianConfig();

    expect($config['rate_limit'])->toBe(['per_minute' => 120])
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
    ])->and($config['tables'])->toBe([
        'sso_connections' => 'guardian_sso_connections',
        'sso_identities' => 'guardian_sso_identities',
        'scim_tokens' => 'guardian_scim_tokens',
        'scim_users' => 'guardian_scim_users',
        'scim_groups' => 'guardian_scim_groups',
    ]);
});

it('places the tabs and section on Roster\'s screens, and links back to the organization', function (): void {
    expect(shippedGuardianConfig()['atrium'])->toBe([
        'screens' => [
            'roster.organization' => ['guardian.sso', 'guardian.scim'],
            'roster.user' => ['aside' => 'guardian.sso-identities'],
        ],
        'organization_route' => 'atrium.roster.organizations.show',
    ]);
});

it('turns its Atrium screens on by default, as every suite package does (D-038)', function (): void {
    expect(shippedGuardianConfig()['ui'])->toBe(['enabled' => true])
        ->and(config('guardian.ui.enabled'))->toBeTrue();
});

it('has exactly the documented sections, each under a banner', function (): void {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/config/guardian.php');

    expect(array_keys(shippedGuardianConfig()))->toBe([
        'sso', 'scim', 'routes', 'rate_limit', 'audit', 'policies', 'tables', 'mcp', 'cortex', 'migrations', 'ui', 'atrium',
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
