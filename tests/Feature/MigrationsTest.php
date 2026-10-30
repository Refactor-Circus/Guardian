<?php

declare(strict_types=1);

use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Exceptions\GuardianException;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/*
 * The create migrations (criterion 21): Roster's columns, keys and indexes
 * under Guardian's table names, a create skipped for a table already in
 * place, and a down() that refuses to drop rows unless forced (D-031).
 */

const GUARDIAN_TABLES = [
    'guardian_sso_connections',
    'guardian_sso_identities',
    'guardian_scim_tokens',
    'guardian_scim_users',
    'guardian_scim_groups',
];

function guardianMigrations(): string
{
    return (string) realpath(dirname(__DIR__, 2).'/database/migrations');
}

/**
 * Roll back Guardian's migrations, the last batch.
 *
 * @return array<int, string> the migrations rolled back, in order
 */
function rollBackGuardian(): array
{
    $rolledBack = [];

    Event::listen(MigrationEnded::class, function (MigrationEnded $event) use (&$rolledBack): void {
        $rolledBack[] = basename((string) (new ReflectionClass($event->migration))->getFileName(), '.php');
    });

    Artisan::call('migrate:rollback', ['--path' => guardianMigrations(), '--realpath' => true]);

    return $rolledBack;
}

function insertScimToken(OrganizationModel $organization): void
{
    DB::table('guardian_scim_tokens')->insert([
        'id' => (string) Str::ulid(),
        'organization_id' => $organization->id,
        'name' => 'Okta',
        'token_hash' => str_repeat('b', 64),
    ]);
}

/**
 * @return array<int, string>
 */
function guardianTablesPresent(): array
{
    return array_values(array_filter(GUARDIAN_TABLES, fn (string $table): bool => Schema::hasTable($table)));
}

it('creates the five tables', function (): void {
    expect(guardianTablesPresent())->toBe(GUARDIAN_TABLES);
});

it('keeps Roster\'s columns', function (string $table, array $columns): void {
    expect(Schema::getColumnListing($table))->toEqualCanonicalizing($columns);
})->with([
    ['guardian_sso_connections', ['id', 'organization_id', 'name', 'slug', 'protocol', 'config', 'jit', 'enforced', 'enabled', 'created_at', 'updated_at']],
    ['guardian_sso_identities', ['id', 'connection_id', 'user_id', 'subject', 'email', 'last_login_at', 'created_at', 'updated_at']],
    ['guardian_scim_tokens', ['id', 'organization_id', 'name', 'token_hash', 'sso_connection_id', 'created_by', 'last_used_at', 'expires_at', 'revoked_at', 'created_at', 'updated_at']],
    ['guardian_scim_users', ['id', 'organization_id', 'user_id', 'external_id', 'created_by_scim', 'deactivated_by_scim', 'active', 'created_at', 'updated_at']],
    ['guardian_scim_groups', ['id', 'organization_id', 'team_id', 'external_id', 'created_at', 'updated_at']],
]);

it('keeps Roster\'s unique keys', function (string $table, array $columns): void {
    expect(Schema::hasIndex($table, $columns, 'unique'))->toBeTrue();
})->with([
    ['guardian_sso_connections', ['slug']],
    ['guardian_sso_identities', ['connection_id', 'subject']],
    ['guardian_scim_tokens', ['token_hash']],
    ['guardian_scim_users', ['organization_id', 'user_id']],
    ['guardian_scim_groups', ['team_id']],
]);

it('has the lookup and list indexes', function (string $table, string $index, array $columns): void {
    expect(Schema::hasIndex($table, $index))->toBeTrue($index.' is missing')
        ->and(Schema::hasIndex($table, $columns))->toBeTrue();
})->with([
    ['guardian_sso_connections', 'guardian_sso_connections_organization_id_lookup_index', ['organization_id']],
    ['guardian_sso_identities', 'guardian_sso_identities_user_id_index', ['user_id']],
    ['guardian_scim_tokens', 'guardian_scim_tokens_organization_id_lookup_index', ['organization_id']],
    ['guardian_scim_tokens', 'guardian_scim_tokens_sso_connection_id_lookup_index', ['sso_connection_id']],
    ['guardian_scim_users', 'guardian_scim_users_organization_id_external_id_index', ['organization_id', 'external_id']],
    ['guardian_scim_users', 'guardian_scim_users_organization_created_index', ['organization_id', 'created_at', 'id']],
    ['guardian_scim_groups', 'guardian_scim_groups_organization_id_lookup_index', ['organization_id']],
    ['guardian_scim_groups', 'guardian_scim_groups_organization_created_index', ['organization_id', 'created_at', 'id']],
]);

it('references Roster\'s organizations and teams, and nulls a token\'s connection when it goes', function (): void {
    $organization = OrganizationModel::factory()->create();
    $connection = SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);

    DB::table('guardian_scim_tokens')->insert([
        'id' => (string) Str::ulid(),
        'organization_id' => $organization->id,
        'name' => 'Okta',
        'token_hash' => str_repeat('a', 64),
        'sso_connection_id' => $connection->id,
    ]);

    $connection->delete();

    expect(DB::table('guardian_scim_tokens')->value('sso_connection_id'))->toBeNull();

    $organization->forceDelete();

    expect(DB::table('guardian_scim_tokens')->count())->toBe(0);
});

it('drops empty tables on rollback, in reverse order', function (): void {
    expect(rollBackGuardian())->toBe([
        '2026_11_01_000002_create_guardian_scim_tables',
        '2026_11_01_000001_create_guardian_sso_tables',
    ])->and(guardianTablesPresent())->toBe([]);
});

it('refuses to roll back over rows, naming the tables that hold them', function (): void {
    $organization = OrganizationModel::factory()->create();
    SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);

    expect(fn () => rollBackGuardian())
        ->toThrow(GuardianException::class, 'guardian_sso_connections');

    // D-040: the empty SCIM tables went with their own migration; the SSO
    // migration refused and kept its tables and rows.
    expect(guardianTablesPresent())->toBe(['guardian_sso_connections', 'guardian_sso_identities'])
        ->and(SsoConnectionModel::query()->count())->toBe(1);
});

it('names only the tables that hold rows', function (): void {
    $organization = OrganizationModel::factory()->create();
    SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);

    try {
        rollBackGuardian();
    } catch (GuardianException $e) {
        expect($e->getMessage())->toContain('[guardian_sso_connections]')
            ->and($e->getMessage())->not->toContain('guardian_scim_tokens');

        return;
    }

    $this->fail('The rollback dropped tables that hold rows.');
});

it('refuses the SCIM rollback over SCIM rows, before the SSO migration runs', function (): void {
    insertScimToken(OrganizationModel::factory()->create());

    expect(fn () => rollBackGuardian())
        ->toThrow(GuardianException::class, '[guardian_scim_tokens]');

    expect(guardianTablesPresent())->toBe(GUARDIAN_TABLES)
        ->and(DB::table('guardian_scim_tokens')->count())->toBe(1);
});

it('checks only the tables each migration drops', function (): void {
    $organization = OrganizationModel::factory()->create();
    $sso = require guardianMigrations().'/2026_11_01_000001_create_guardian_sso_tables.php';
    $scim = require guardianMigrations().'/2026_11_01_000002_create_guardian_scim_tables.php';

    // SCIM rows do not hold back the SSO migration (D-040)...
    insertScimToken($organization);
    $sso->down();

    expect(Schema::hasTable('guardian_sso_connections'))->toBeFalse()
        ->and(Schema::hasTable('guardian_sso_identities'))->toBeFalse()
        ->and(DB::table('guardian_scim_tokens')->count())->toBe(1);

    // ...and SSO rows do not hold back the SCIM migration.
    $sso->up();
    DB::table('guardian_scim_tokens')->delete();
    SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);
    $scim->down();

    expect(Schema::hasTable('guardian_scim_tokens'))->toBeFalse()
        ->and(Schema::hasTable('guardian_scim_users'))->toBeFalse()
        ->and(Schema::hasTable('guardian_scim_groups'))->toBeFalse()
        ->and(SsoConnectionModel::query()->count())->toBe(1);
});

it('drops tables with rows when guardian.migrations.force_rollback is on', function (): void {
    $organization = OrganizationModel::factory()->create();
    SsoConnectionModel::factory()->create(['organization_id' => $organization->id]);

    config()->set('guardian.migrations.force_rollback', true);

    rollBackGuardian();

    expect(guardianTablesPresent())->toBe([]);
});

it('skips creating a table that is already in place', function (): void {
    rollBackGuardian();

    // As the upgrade leaves it: Roster's table renamed into place, rows and all.
    Schema::create(Tables::name('sso_connections'), function (Blueprint $table): void {
        $table->ulid('id')->primary();
        $table->string('carried_over');
    });
    DB::table(Tables::name('sso_connections'))->insert(['id' => (string) Str::ulid(), 'carried_over' => 'yes']);

    Artisan::call('migrate', ['--path' => guardianMigrations(), '--realpath' => true]);

    expect(Schema::hasColumn('guardian_sso_connections', 'carried_over'))->toBeTrue()
        ->and(DB::table('guardian_sso_connections')->count())->toBe(1)
        ->and(guardianTablesPresent())->toBe(GUARDIAN_TABLES);
});
