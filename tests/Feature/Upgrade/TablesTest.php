<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Guardian\Upgrade\UpgradeReport;

/*
 * Criterion 22, tables: plan every table, drop the empty Guardian tables in
 * the way (children first), rename Roster's into place (parents first), and
 * leave both alone when Guardian's already hold rows. Each case runs on
 * SQLite with foreign keys enforced; the rename and FK-survival cases also
 * run on MySQL 8 in the `mysql` CI job (D-034).
 */

it('does nothing on a fresh install', function (): void {
    dropUpgradeTables('roster');

    $report = runUpgrade();

    expect($report->dropped)->toBe([])
        ->and($report->renamed)->toBe([])
        ->and($report->conflicts)->toBe([])
        ->and(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 0));
})->group('upgrade-mysql');

it('renames Roster\'s tables into place, keeping rows, ids and the token\'s connection key', function (): void {
    dropUpgradeTables('guardian');
    $ids = seedUpgradeRows('roster');
    // As before Guardian was installed: Roster's permission names only, so
    // their grants are renamed in place and keep their count.
    DB::table('roster_permissions')->where('name', 'like', 'guardian.%')->delete();
    $grants = DB::table('roster_permission_role')->count();

    expect(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));

    $report = runUpgrade();

    expect($report->renamed)->toBe(array_combine(
        array_map(fn (string $key): string => 'roster_'.$key, UPGRADE_KEYS),
        array_map(fn (string $key): string => 'guardian_'.$key, UPGRADE_KEYS),
    ))
        ->and($report->dropped)->toBe([])
        ->and(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, null))
        ->and(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 1))
        ->and(DB::table('roster_permission_role')->count())->toBe($grants);

    foreach (UPGRADE_KEYS as $key) {
        expect(DB::table('guardian_'.$key)->value('id'))->toBe($ids[$key]);
    }

    $token = ScimTokenModel::query()->sole();

    expect($token->sso_connection_id)->toBe($ids['sso_connections'])
        ->and(SsoConnectionModel::query()->sole()->config)->toBe(['issuer' => 'https://idp.test']);

    // The foreign key followed the rename: deleting the connection still
    // nulls the token's key, with foreign keys enforced.
    DB::table('guardian_sso_identities')->delete();
    SsoConnectionModel::query()->sole()->delete();

    expect(DB::table('guardian_scim_tokens')->value('sso_connection_id'))->toBeNull();
})->group('upgrade-mysql');

it('drops all five empty Guardian tables, children first, then renames Roster\'s', function (): void {
    seedUpgradeRows('roster');

    expect(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 0));

    $report = runUpgrade();

    expect($report->dropped)->toBe(array_map(fn (string $key): string => 'guardian_'.$key, array_reverse(UPGRADE_KEYS)))
        ->and(array_keys($report->renamed))->toBe(array_map(fn (string $key): string => 'roster_'.$key, UPGRADE_KEYS))
        ->and(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, null))
        ->and(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));
})->group('upgrade-mysql');

it('leaves a state the next run completes when it fails after the drop pass', function (): void {
    seedUpgradeRows('roster');

    $failed = false;
    DB::connection()->beforeExecuting(function (string $query) use (&$failed): void {
        if (! $failed && str_contains(strtolower($query), 'rename')) {
            $failed = true;

            throw new RuntimeException('Injected failure before the first rename.');
        }
    });

    expect(fn () => runUpgrade())->toThrow(RuntimeException::class, 'Injected failure');

    expect(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, null))
        ->and(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));

    runUpgrade();

    expect(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, null))
        ->and(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));
});

it('changes neither table, and reports it, when both hold rows', function (): void {
    seedUpgradeRows('roster');
    DB::table('guardian_sso_connections')->insert([
        'id' => '01JGUARDIANCONNECTION00000', 'organization_id' => DB::table('roster_organizations')->value('id'),
        'name' => 'Entra', 'slug' => 'acme-entra', 'protocol' => 'azure', 'config' => encrypt('[]', false),
    ]);

    $report = runUpgrade();

    expect($report->conflicts)->toBe(['roster_sso_connections' => 'guardian_sso_connections'])
        ->and(DB::table('roster_sso_connections')->pluck('slug')->all())->toBe(['acme-sso'])
        ->and(DB::table('guardian_sso_connections')->pluck('slug')->all())->toBe(['acme-entra'])
        ->and(implode("\n", $report->lines()))->toContain('[roster_sso_connections]');
});

it('is a no-op the second time', function (): void {
    seedUpgradeRows('roster');
    runUpgrade();

    $report = runUpgrade();

    expect($report->dropped)->toBe([])
        ->and($report->renamed)->toBe([])
        ->and($report->conflicts)->toBe([])
        ->and($report->grants)->toBe([])
        ->and(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 1));
});

it('changes nothing while Roster still owns SSO and SCIM', function (): void {
    $this->app->instance(Handover::class, new Handover(fn (string $class): bool => true));
    seedUpgradeRows('roster');

    $report = runUpgrade();

    expect($report->pending)->toBeTrue()
        ->and($report->lines())->toBe([UpgradeReport::PENDING])
        ->and(upgradeRowCounts('roster'))->toBe(array_fill_keys(UPGRADE_KEYS, 1))
        ->and(upgradeRowCounts('guardian'))->toBe(array_fill_keys(UPGRADE_KEYS, 0))
        ->and(DB::table('roster_permissions')->where('name', 'roster.sso.manage')->exists())->toBeTrue();
});

it('reads Roster\'s table names from Roster\'s config', function (): void {
    dropUpgradeTables('guardian');
    Schema::rename('roster_sso_connections', 'legacy_sso_connections');
    config()->set('roster.tables.sso_connections', 'legacy_sso_connections');

    $report = runUpgrade();

    expect($report->renamed)->toHaveKey('legacy_sso_connections')
        ->and(Schema::hasTable('guardian_sso_connections'))->toBeTrue();
});
