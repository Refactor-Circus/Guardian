<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Guardian\Upgrade\UpgradeReport;

/*
 * Criterion 22: `php artisan guardian:upgrade-from-roster` and the
 * `2026_11_01_000000` migration run the upgrade and report it.
 */

beforeEach(function (): void {
    seedRosterPermissions();
});

function guardianMigrationsPath(): string
{
    return (string) realpath(dirname(__DIR__, 3).'/database/migrations');
}

it('prints the report', function (): void {
    seedUpgradeRows('roster');

    $this->artisan('guardian:upgrade-from-roster')
        ->expectsOutputToContain('Dropped the empty table [guardian_scim_groups]')
        ->expectsOutputToContain('Renamed [roster_sso_connections] to [guardian_sso_connections].')
        ->expectsOutputToContain('Moved the [roster.sso.manage] permission and its grants to [guardian.sso.manage].')
        ->assertSuccessful();
});

it('says the handover is pending, and changes nothing', function (): void {
    $this->app->instance(Handover::class, new Handover(fn (string $class): bool => true));
    seedUpgradeRows('roster');

    $this->artisan('guardian:upgrade-from-roster')
        ->expectsOutputToContain(UpgradeReport::PENDING)
        ->assertSuccessful();

    expect(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));
});

it('upgrades an install Roster migrated and seeded, through Guardian\'s migrations and then the command', function (): void {
    // Guardian is not installed yet: only Roster's tables exist, made by
    // Roster's own create migrations at the pinned SHA (tests/Fixtures).
    Artisan::call('migrate:rollback', ['--path' => guardianMigrationsPath(), '--realpath' => true]);
    expect(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, null));

    // Seeded as Roster's models wrote them: the installed Roster may no
    // longer ship those models, so the rows go in as stored.
    $ids = seedUpgradeRows('roster');

    Log::spy();

    Artisan::call('migrate', ['--path' => guardianMigrationsPath(), '--realpath' => true]);

    Log::shouldHaveReceived('info')->withArgs(fn (string $line): bool => str_contains($line, 'Renamed [roster_sso_connections] to [guardian_sso_connections].'));

    expect(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, null))
        ->and(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 1))
        ->and(SsoConnectionModel::query()->sole()->id)->toBe($ids['sso_connections'])
        ->and(SsoConnectionModel::query()->sole()->config)->toBe(['issuer' => 'https://idp.test'])
        ->and(ScimTokenModel::query()->sole()->sso_connection_id)->toBe($ids['sso_connections'])
        ->and(DB::table('migrations')->where('migration', '2026_11_01_000000_upgrade_guardian_from_roster')->exists())->toBeTrue();

    $this->artisan('guardian:upgrade-from-roster')->assertSuccessful();

    expect(SsoConnectionModel::query()->count())->toBe(1)
        ->and(Schema::hasTable('guardian_scim_tokens'))->toBeTrue();
})->group('upgrade-mysql');

it('records the migration while the handover is pending, so the command finishes later', function (): void {
    $this->app->instance(Handover::class, new Handover(fn (string $class): bool => true));
    seedUpgradeRows('roster');
    Log::spy();

    Artisan::call('migrate', ['--path' => guardianMigrationsPath(), '--realpath' => true]);

    Log::shouldHaveReceived('warning')->withArgs(fn (string $line): bool => str_contains($line, UpgradeReport::PENDING));

    expect(DB::table('migrations')->where('migration', '2026_11_01_000000_upgrade_guardian_from_roster')->exists())->toBeTrue()
        ->and(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));

    $this->app->instance(Handover::class, new Handover(fn (string $class): bool => false));
    $this->artisan('guardian:upgrade-from-roster')->assertSuccessful();

    expect(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));
});

it('undoes nothing on rollback', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/2026_11_01_000000_upgrade_guardian_from_roster.php';
    seedUpgradeRows('roster');
    runUpgrade();

    $migration->down();

    expect(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));
});
