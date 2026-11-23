<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tools\SearchTools;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Guardian\Domains\DomainServiceProvider;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Services\GuardianRoles;
use RefactorCircus\Guardian\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Guardian\Domains\Sso\SsoServiceProvider;
use RefactorCircus\Guardian\GuardianServiceProvider;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Support\Integration;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Keystone\Packages\PackageRegistry;

/*
 * Without Roster, Guardian works (criterion 3, D-003): it boots, migrates and
 * registers everything but SSO and SCIM, which build on Roster's users and
 * organizations, and `php artisan about` says what SSO and SCIM need. These
 * tests also run in the `standalone` CI job, with Roster and Keen
 * uninstalled, so they name none of their classes.
 */

/**
 * Guardian's section of `php artisan about`.
 *
 * @return array<string, mixed>
 */
function standaloneAbout(): array
{
    Artisan::call('about', ['--only' => 'guardian', '--json' => true]);

    /** @var array<string, array<string, mixed>> $about */
    $about = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    return $about['guardian'] ?? [];
}

/**
 * Asserts Guardian registered everything but SSO and SCIM.
 */
function assertOnlySsoAndScimSkipped(): void
{
    $routes = array_filter(
        array_keys(Route::getRoutes()->getRoutesByName()),
        fn (string $name): bool => str_starts_with($name, 'guardian.') && (str_contains($name, 'sso') || str_contains($name, 'scim')),
    );

    $tools = (new GuardianServer(new FakeTransporter))->createContext()->tools()
        ->map(fn (object $tool): string => $tool::class)
        ->all();

    $migrationPaths = array_map(fn (string $path): string|false => realpath($path), app('migrator')->paths());

    expect($routes)->toBe([])
        ->and(app()->getProviders(DomainServiceProvider::class))->not->toBe([])
        ->and(app()->getProviders(SsoServiceProvider::class))->toBe([])
        ->and(app()->getProviders(ScimServiceProvider::class))->toBe([])
        ->and(array_filter($tools, fn (string $tool): bool => str_contains($tool, '\\Domains\\Sso\\') || str_contains($tool, '\\Domains\\Scim\\')))->toBe([])
        ->and($migrationPaths)->toContain(realpath(dirname(__DIR__, 3).'/database/migrations'))
        ->and($migrationPaths)->not->toContain(realpath(dirname(__DIR__, 3).'/database/migrations/roster'))
        ->and(array_keys(Artisan::all()))->not->toContain('guardian:upgrade-from-roster')
        ->and(Gate::policies())->not->toBe([]);
}

it('does not see Roster', function (): void {
    expect(app(Integration::class)->rosterInstalled())->toBeFalse()
        ->and(app(Integration::class)->rosterPresent())->toBeFalse();
});

it('is active, and says in php artisan about that SSO and SCIM need Roster', function (): void {
    expect(standaloneAbout())->toBe([
        'status' => 'Active',
        'sso_and_scim' => 'Inactive: install refactor-circus/roster',
    ]);
});

it('skips only the SSO and SCIM providers, routes, tools, migrations and upgrade command', function (): void {
    assertOnlySsoAndScimSkipped();
});

it('migrates without Roster: the role tables and built-in roles, but no SSO or SCIM table', function (): void {
    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0)
        ->and(Schema::hasTable('guardian_roles'))->toBeTrue()
        ->and(Schema::hasTable('guardian_role_assignments'))->toBeTrue()
        ->and(DB::table('guardian_roles')->orderBy('slug')->pluck('slug')->all())->toBe(['admin', 'member', 'super-admin'])
        ->and(DB::table('guardian_permissions')->where('name', 'guardian.roles.view')->exists())->toBeTrue()
        ->and(Schema::hasTable('guardian_sso_connections'))->toBeFalse()
        ->and(Schema::hasTable('guardian_scim_tokens'))->toBeFalse()
        ->and(array_keys(Artisan::all()))->toContain('guardian:sync-permissions');
});

it('is Keystone\'s role store without Roster', function (): void {
    expect(app(Permissions::class))->toBeInstanceOf(GuardianPermissions::class)
        ->and(app(Roles::class))->toBeInstanceOf(GuardianRoles::class);
});

it('boots its config, translations, package entry and rate limiter', function (): void {
    expect(config('guardian.sso.prefix'))->toBe('roster/sso')
        ->and(config('guardian.scim.middleware'))->toBe(['api', 'throttle:guardian'])
        ->and(__('guardian::guardian.sso_not_found'))->toBe('No single sign-on is set up for this email domain.')
        ->and(app(PackageRegistry::class)->has('guardian'))->toBeTrue()
        ->and(RateLimiter::limiter('guardian'))->not->toBeNull()
        ->and(ServiceProvider::pathsToPublish(GuardianServiceProvider::class, 'guardian-config'))->toHaveCount(1);
});

describe('a Roster without the seams Guardian uses', function (): void {
    it('skips SSO and SCIM, saying a newer Roster is needed', function (Integration $integration): void {
        $this->bootWith($integration);

        expect(app(Integration::class)->rosterInstalled())->toBeFalse()
            ->and(app(Integration::class)->rosterPresent())->toBeTrue()
            ->and(standaloneAbout())->toBe([
                'status' => 'Active',
                'sso_and_scim' => 'Inactive: needs a newer refactor-circus/roster',
            ]);

        assertOnlySsoAndScimSkipped();
    })->with([
        // Roster's class is there, but neither requireSignIn() nor provisionedStatus().
        'without the seam methods' => fn (): Integration => new Integration(
            fn (string $class): bool => true,
            fn (string $class, string $method): bool => false,
        ),
        // Roster and its methods are there, but not the membership sources seam.
        'without the seam classes' => fn (): Integration => new Integration(
            fn (string $class): bool => $class === 'RefactorCircus\\Roster\\Roster',
            fn (string $class, string $method): bool => true,
        ),
    ]);
});

it('serves the role, permission and security log API without Roster (criterion 3)', function (): void {
    $names = array_values(array_filter(
        array_keys(Route::getRoutes()->getRoutesByName()),
        fn (string $name): bool => str_starts_with($name, 'guardian.roles.') || str_starts_with($name, 'guardian.permissions.') || str_contains($name, 'security-events'),
    ));

    $search = (new GuardianServer(new FakeTransporter))->createContext()->tools()
        ->first(fn (object $tool): bool => $tool instanceof SearchTools);

    expect($search)->toBeInstanceOf(SearchTools::class);

    /** @var SearchTools $search */
    $catalog = json_decode((string) $search->handle(new Request(['query' => '', 'limit' => 50]))->content(), true, 512, JSON_THROW_ON_ERROR);
    $tools = array_column($catalog['tools'], 'name');

    expect($names)->toContain('guardian.roles.index', 'guardian.roles.store', 'guardian.roles.show', 'guardian.roles.update', 'guardian.roles.destroy')
        ->and($names)->toContain('guardian.permissions.index', 'guardian.security-events.index', 'guardian.security-events.show', 'guardian.me.security-events.index')
        ->and($tools)->toContain('list-roles-tool', 'show-role-tool', 'create-role-tool', 'update-role-tool', 'delete-role-tool')
        ->and($tools)->toContain('list-permissions-tool', 'list-role-assignments-tool', 'list-security-events-tool', 'list-my-security-events-tool')
        ->and(array_filter($tools, fn (string $tool): bool => str_contains($tool, 'sso') || str_contains($tool, 'scim')))->toBe([]);
});
