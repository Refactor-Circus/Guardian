<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Exceptions\GuardianException;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/*
 * Every table renamed through `guardian.tables` (criterion 5): the models
 * and the migrations both use the configured names.
 */

it('migrates into the renamed tables', function (string $key): void {
    expect(Schema::hasTable('acme_'.$key))->toBeTrue()
        ->and(Schema::hasTable(Tables::DEFAULTS[$key]))->toBeFalse();
})->with(array_keys(Tables::DEFAULTS));

it('gives each model its renamed table', function (string $model, string $table): void {
    expect((new $model)->getTable())->toBe($table);
})->with([
    [SsoConnectionModel::class, 'acme_sso_connections'],
    [SsoIdentityModel::class, 'acme_sso_identities'],
    [ScimTokenModel::class, 'acme_scim_tokens'],
    [ScimUserModel::class, 'acme_scim_users'],
    [ScimGroupModel::class, 'acme_scim_groups'],
]);

it('names the indexes after the renamed tables', function (): void {
    expect(Schema::hasIndex('acme_sso_connections', 'acme_sso_connections_organization_id_lookup_index'))->toBeTrue()
        ->and(Schema::hasIndex('acme_scim_users', 'acme_scim_users_organization_created_index'))->toBeTrue()
        ->and(Schema::hasIndex('acme_scim_groups', 'acme_scim_groups_organization_created_index'))->toBeTrue();
});

it('stores and reads a connection through the renamed table', function (): void {
    $organization = OrganizationModel::factory()->create();
    $connection = SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);

    expect(DB::table('acme_sso_connections')->where('id', $connection->id)->exists())->toBeTrue()
        ->and(SsoConnectionModel::query()->find($connection->id)?->slug)->toBe($connection->slug);
});

it('names the renamed table when it refuses to roll back over rows', function (): void {
    $organization = OrganizationModel::factory()->create();
    SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);

    expect(fn () => Artisan::call('migrate:rollback', ['--path' => realpath(dirname(__DIR__, 3).'/database/migrations'), '--realpath' => true]))
        ->toThrow(GuardianException::class, '[acme_sso_connections]');

    expect(Schema::hasTable('acme_scim_tokens'))->toBeTrue();
});
