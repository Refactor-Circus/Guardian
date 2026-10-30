<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Guardian\Upgrade\UpgradeReport;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;

/*
 * Criteria 28 and 31: while the installed Roster still ships SSO and SCIM,
 * Guardian serves neither its SSO web routes nor its SCIM server routes, keeps
 * no morph aliases, and the upgrade changes nothing. Everything that does not
 * depend on the handover still registers.
 */

it('sees the handover pending, because Roster still ships its SSO connection model', function (): void {
    expect(class_exists('RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoConnectionModel'))->toBeTrue()
        ->and(app(Handover::class)->complete())->toBeFalse();
});

it('loads neither the SSO web routes nor the SCIM server routes', function (): void {
    expect(Route::has('guardian.sso.start'))->toBeFalse()
        ->and(Route::has('guardian.sso.callback'))->toBeFalse()
        ->and(Route::has('guardian.scim.users.index'))->toBeFalse();
});

it('still loads the API routes, permissions and resources', function (): void {
    expect(Route::has('guardian.organizations.sso-connections.index'))->toBeTrue()
        ->and(Route::has('guardian.organizations.scim-tokens.index'))->toBeTrue()
        ->and(app(Permissions::class)->knows('guardian.sso.manage'))->toBeTrue()
        ->and(app(Permissions::class)->knows('guardian.scim.manage'))->toBeTrue();
});

it('registers no morph aliases for Roster\'s class names', function (): void {
    $guardianModels = [SsoConnectionModel::class, SsoIdentityModel::class, ScimTokenModel::class, ScimUserModel::class, ScimGroupModel::class];

    expect(array_intersect(Relation::morphMap(), $guardianModels))->toBe([])
        ->and((new SsoConnectionModel)->getMorphClass())->toBe(SsoConnectionModel::class);
});

it('does not run the upgrade: Roster\'s tables stay, and Guardian creates its own', function (): void {
    foreach (['sso_connections', 'sso_identities', 'scim_tokens', 'scim_users', 'scim_groups'] as $key) {
        expect(Schema::hasTable('roster_'.$key))->toBeTrue('roster_'.$key.' was moved')
            ->and(Schema::hasTable('guardian_'.$key))->toBeTrue('guardian_'.$key.' is missing');
    }
});

it('says so when the command is run', function (): void {
    $this->artisan('guardian:upgrade-from-roster')
        ->expectsOutputToContain(UpgradeReport::PENDING)
        ->assertSuccessful();

    expect(Schema::hasTable('roster_sso_connections'))->toBeTrue();
});
