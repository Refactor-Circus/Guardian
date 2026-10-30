<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RefactorCircus\Guardian\Domains\DomainServiceProvider;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimGroupPolicy;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimTokenPolicy;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimUserPolicy;
use RefactorCircus\Guardian\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoIdentityPolicy;
use RefactorCircus\Guardian\Domains\Sso\SsoServiceProvider;
use RefactorCircus\Guardian\Facades\Guardian as GuardianFacade;
use RefactorCircus\Guardian\Guardian;
use RefactorCircus\Guardian\GuardianServiceProvider;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Support\Integration;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Keystone\Database\Tables as KeystoneTables;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/*
 * The package shape (criterion 5): the registration, the domain providers,
 * the table names and the policies, with Roster installed.
 */

it('registers the guardian package with its label and MCP server', function (): void {
    $package = app(PackageRegistry::class)->get('guardian');

    expect($package->key)->toBe('guardian')
        ->and($package->label)->toBe('Guardian')
        ->and($package->namespace)->toBe('RefactorCircus\\Guardian')
        ->and($package->server)->toBe(GuardianServer::class);
});

it('registers the SSO and SCIM domain providers through the domain provider', function (): void {
    expect(app()->getProviders(DomainServiceProvider::class))->not->toBeEmpty()
        ->and(app()->getProviders(SsoServiceProvider::class))->not->toBeEmpty()
        ->and(app()->getProviders(ScimServiceProvider::class))->not->toBeEmpty();
});

it('names its tables through Tables, on Keystone\'s Tables', function (): void {
    expect(is_subclass_of(Tables::class, KeystoneTables::class))->toBeTrue()
        ->and(Tables::DEFAULTS)->toBe([
            'sso_connections' => 'guardian_sso_connections',
            'sso_identities' => 'guardian_sso_identities',
            'scim_tokens' => 'guardian_scim_tokens',
            'scim_users' => 'guardian_scim_users',
            'scim_groups' => 'guardian_scim_groups',
        ]);

    config()->set('guardian.tables.scim_users', 'acme_scim_users');

    expect(Tables::name('scim_users'))->toBe('acme_scim_users')
        ->and(Tables::name('scim_groups'))->toBe('guardian_scim_groups');
});

it('gives each model its table from Tables', function (string $model, string $table): void {
    expect((new $model)->getTable())->toBe($table);
})->with([
    [SsoConnectionModel::class, 'guardian_sso_connections'],
    [SsoIdentityModel::class, 'guardian_sso_identities'],
    [ScimTokenModel::class, 'guardian_scim_tokens'],
    [ScimUserModel::class, 'guardian_scim_users'],
    [ScimGroupModel::class, 'guardian_scim_groups'],
]);

it('registers each model\'s policy from guardian.policies', function (string $model, string $policy): void {
    expect(config('guardian.policies')[$model] ?? null)->toBe($policy)
        ->and(Gate::getPolicyFor($model))->toBeInstanceOf($policy);
})->with([
    [SsoConnectionModel::class, SsoConnectionPolicy::class],
    [SsoIdentityModel::class, SsoIdentityPolicy::class],
    [ScimTokenModel::class, ScimTokenPolicy::class],
    [ScimUserModel::class, ScimUserPolicy::class],
    [ScimGroupModel::class, ScimGroupPolicy::class],
]);

it('stores connections through the moved model, with its lifecycle events', function (): void {
    $organization = OrganizationModel::factory()->create();

    $connection = SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);

    expect($connection->organization->is($organization))->toBeTrue()
        ->and($connection->getAttributes()['config'])->not->toContain('top-secret')
        ->and($connection->fresh()?->config['client_secret'] ?? null)->toBe('top-secret');
});

it('reads Roster and Atrium as installed', function (): void {
    $integration = app(Integration::class);

    expect($integration->rosterInstalled())->toBeTrue()
        ->and($integration->atriumInstalled())->toBeTrue()
        ->and(app(Integration::class))->toBe($integration);
});

it('shows Guardian as active in php artisan about', function (): void {
    Artisan::call('about', ['--only' => 'guardian', '--json' => true]);

    expect(json_decode(Artisan::output(), true)['guardian'] ?? null)->toMatchArray(['status' => 'Active']);
});

it('loads and publishes its migrations', function (): void {
    $path = realpath(dirname(__DIR__, 2).'/database/migrations');

    expect(array_map(realpath(...), app('migrator')->paths()))->toContain($path)
        ->and(array_map(realpath(...), array_keys(ServiceProvider::pathsToPublish(GuardianServiceProvider::class, 'guardian-migrations'))))->toContain($path);
});

it('publishes its config and translations', function (): void {
    expect(ServiceProvider::pathsToPublish(GuardianServiceProvider::class, 'guardian-config'))->toHaveCount(1)
        ->and(ServiceProvider::pathsToPublish(GuardianServiceProvider::class, 'guardian-lang'))->toHaveCount(1);
});

it('loads its translations', function (): void {
    expect(__('guardian::guardian.sso'))->toBe('Single sign-on')
        ->and(__('guardian::guardian.scim'))->toBe('SCIM provisioning');
});

it('resolves one Guardian through the container and the facade', function (): void {
    expect(app(Guardian::class))->toBe(app(Guardian::class))
        ->and(GuardianFacade::getFacadeRoot())->toBe(app(Guardian::class));
});
