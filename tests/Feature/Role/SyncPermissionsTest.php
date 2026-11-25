<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Support\BuiltInRoles;
use RefactorCircus\Keystone\Authorization\Abilities;

/*
 * Criteria 7 and 8: `guardian:sync-permissions` (also run by the migration)
 * creates a system permission per Keystone ability and the missing built-in
 * roles, granting `grantTo` only to a role it creates; Guardian defines its
 * own abilities.
 */

const GUARDIAN_ABILITIES = [
    'guardian.roles.view',
    'guardian.roles.manage',
    'guardian.roles.assign',
    'guardian.sso.view',
    'guardian.sso.manage',
    'guardian.scim.manage',
    'guardian.two-factor.manage',
    'guardian.sessions.manage',
    'guardian.tokens.manage',
    'guardian.security.view',
    'guardian.security.manage',
];

/**
 * The permissions a built-in role holds, leaving out other installed
 * packages' (Roster's `roster.*`).
 *
 * @return list<string>
 */
function grantsOf(string $slug): array
{
    return RoleModel::query()->whereNull('owner_type')->where('slug', $slug)->sole()
        ->permissions()->where('name', 'not like', 'roster.%')->orderBy('name')->pluck('name')->map(strval(...))->all();
}

it('defines Guardian\'s abilities in Keystone\'s Abilities, with criterion 8\'s grants', function (): void {
    $abilities = app(Abilities::class)->all();

    expect(array_keys(array_intersect_key($abilities, array_flip(GUARDIAN_ABILITIES))))->toEqualCanonicalizing(GUARDIAN_ABILITIES);

    $toAdmin = array_keys(array_filter(
        array_intersect_key($abilities, array_flip(GUARDIAN_ABILITIES)),
        fn (array $ability): bool => in_array('admin', $ability['grantTo'], true),
    ));

    expect($toAdmin)->toEqualCanonicalizing([
        'guardian.roles.view',
        'guardian.roles.assign',
        'guardian.sso.view',
        'guardian.sso.manage',
        'guardian.scim.manage',
    ]);

    foreach (GUARDIAN_ABILITIES as $ability) {
        expect($abilities[$ability]['description'])->not->toBe('');
    }
});

it('creates a system permission for every Keystone ability when the tables are created', function (): void {
    $names = PermissionModel::query()->where('system', true)->pluck('description', 'name')->all();

    foreach (app(Abilities::class)->all() as $ability => $definition) {
        expect($names)->toHaveKey($ability)
            ->and($names[$ability])->toBe($definition['description']);
    }
});

it('grants the built-in roles what Keystone\'s abilities grant them, on creation', function (): void {
    expect(grantsOf('admin'))->toBe([
        'guardian.roles.assign',
        'guardian.roles.view',
        'guardian.scim.manage',
        'guardian.sso.manage',
        'guardian.sso.view',
    ])->and(grantsOf('super-admin'))->toBe([])
        ->and(grantsOf('member'))->toBe([]);
});

it('adds a newly defined ability as a permission, but grants it only to a role it creates', function (): void {
    app(Abilities::class)->define('acme.widgets.view', 'View widgets.', ['admin', 'member', 'ghost']);

    expect(Artisan::call('guardian:sync-permissions'))->toBe(0)
        ->and(PermissionModel::query()->where('name', 'acme.widgets.view')->value('system'))->toBeTrue()
        ->and(grantsOf('admin'))->not->toContain('acme.widgets.view');

    RoleModel::query()->whereNull('owner_type')->where('slug', 'member')->delete();
    BuiltInRoles::sync();

    expect(grantsOf('member'))->toBe(['acme.widgets.view'])
        ->and(RoleModel::query()->where('slug', 'ghost')->exists())->toBeFalse();
});

it('keeps an admin\'s edits to roles and permissions', function (): void {
    $admin = RoleModel::query()->whereNull('owner_type')->where('slug', 'admin')->sole();
    $admin->update(['name' => 'Administrators', 'description' => 'Ours.']);
    $admin->permissions()->detach(PermissionModel::query()->where('name', 'guardian.sso.manage')->value('id'));
    PermissionModel::query()->where('name', 'guardian.roles.view')->update(['description' => 'Edited.']);
    PermissionModel::query()->create(['name' => 'host.reports.view', 'description' => 'Reports.']);

    BuiltInRoles::sync();

    $admin->refresh();

    expect($admin->name)->toBe('Administrators')
        ->and($admin->description)->toBe('Ours.')
        ->and(grantsOf('admin'))->not->toContain('guardian.sso.manage')
        ->and(PermissionModel::query()->where('name', 'guardian.roles.view')->value('description'))->toBe('Edited.')
        ->and(PermissionModel::query()->where('name', 'host.reports.view')->exists())->toBeTrue();
});

it('is idempotent', function (): void {
    $count = fn (): array => [PermissionModel::query()->count(), RoleModel::query()->count(), grantsOf('admin')];
    $before = $count();

    BuiltInRoles::sync();
    Artisan::call('guardian:sync-permissions');

    expect($count())->toBe($before);
});

it('reads the built-in roles from guardian.roles.built_in', function (): void {
    expect(config('guardian.roles.built_in'))->toBe([
        'super-admin' => ['name' => 'Super admin', 'scope' => 'global', 'super' => true],
        'admin' => ['name' => 'Admin', 'scope' => 'any', 'super' => false],
        'member' => ['name' => 'Member', 'scope' => 'any', 'super' => false],
    ]);

    config(['guardian.roles.built_in.auditor' => ['name' => 'Auditor', 'scope' => 'global', 'super' => false]]);
    app(Abilities::class)->define('acme.audit.view', 'Read the audit log.', ['auditor']);

    BuiltInRoles::sync();

    expect(grantsOf('auditor'))->toBe(['acme.audit.view']);
});

it('says what it did', function (): void {
    Artisan::call('guardian:sync-permissions');

    expect(Artisan::output())->toContain('up to date');
});
