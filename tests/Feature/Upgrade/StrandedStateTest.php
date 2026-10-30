<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Guardian\Domains\Sso\Services\SsoSignInRequirement;
use RefactorCircus\Guardian\Exceptions\StrandedTablesException;
use RefactorCircus\Guardian\GuardianServiceProvider;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Guardian\Upgrade\RosterUpgrade;

/*
 * Criterion 22, stranded state (D-029): the handover is complete, Roster's
 * tables still hold the rows and Guardian's are empty, because the upgrade
 * was skipped before Roster let go. Guardian warns, on `about` and through
 * one reported exception per boot, and does not fail closed.
 */

beforeEach(function (): void {
    seedRosterPermissions();
});

const STRANDED = ['roster_sso_connections', 'roster_sso_identities', 'roster_scim_tokens', 'roster_scim_users', 'roster_scim_groups'];

it('finds Roster\'s tables that hold rows Guardian\'s lack', function (): void {
    expect(app(RosterUpgrade::class)->stranded())->toBe([]);

    seedUpgradeRows('roster');

    expect(app(RosterUpgrade::class)->stranded())->toBe(STRANDED);

    runUpgrade();

    expect(app(RosterUpgrade::class)->stranded())->toBe([]);
});

it('finds nothing stranded while the handover is pending', function (): void {
    seedUpgradeRows('roster');
    $this->app->instance(Handover::class, new Handover(fn (string $class): bool => true));

    expect(app(RosterUpgrade::class)->stranded())->toBe([]);
});

it('names the stranded tables and the command on php artisan about', function (): void {
    seedUpgradeRows('roster');

    Artisan::call('about', ['--only' => 'guardian']);
    $about = Artisan::output();

    expect($about)->toContain('[roster_sso_connections]')
        ->and($about)->toContain('[roster_scim_groups]')
        ->and($about)->toContain('guardian:upgrade-from-roster');
});

it('reports one exception per boot naming them', function (): void {
    seedUpgradeRows('roster');
    Exceptions::fake();

    // Boot Guardian's provider again: its booted callback runs at once.
    (new GuardianServiceProvider($this->app))->boot();

    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(fn (StrandedTablesException $e): bool => $e->tables === STRANDED
        && str_contains($e->getMessage(), 'guardian:upgrade-from-roster'));
});

it('reports nothing when nothing is stranded', function (): void {
    Exceptions::fake();

    (new GuardianServiceProvider($this->app))->boot();

    Exceptions::assertNothingReported();
});

it('does not refuse an SSO sign-in for a stranded connection\'s domain', function (): void {
    // The stranded connection is enforced for acme.test, but Guardian reads
    // its own (empty) tables and does not fail closed.
    seedUpgradeRows('roster');

    expect(app(SsoSignInRequirement::class)->for('ada@acme.test'))->toBeNull();

    runUpgrade();

    expect(app(SsoSignInRequirement::class)->for('ada@acme.test')?->key)->toBe('acme-sso');
});
