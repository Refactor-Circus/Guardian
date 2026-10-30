<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';
require_once dirname(__DIR__).'/Authorization/fixtures.php';

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

/*
 * Criterion 22, grants: Roster's SSO and SCIM permissions become Guardian's,
 * and every role keeps what it held. One transaction, so a failed run leaves
 * the old state and the next run completes.
 */

/**
 * The permission names a role holds, among the ones the upgrade moves.
 *
 * @return list<string>
 */
function heldSsoScimPermissions(RoleModel $role): array
{
    return $role->permissions()
        ->where(fn ($query) => $query->where('name', 'like', 'roster.s%')->orWhere('name', 'like', 'guardian.%'))
        ->orderBy('name')
        ->pluck('name')
        ->all();
}

function adminRole(): RoleModel
{
    return RoleModel::query()->whereNull('organization_id')->where('slug', 'admin')->sole();
}

/**
 * A custom role holding one permission, by name.
 */
function customRoleHolding(string $permission): RoleModel
{
    $role = RoleModel::query()->create(['name' => 'Identity', 'slug' => 'identity', 'scope' => 'organization']);

    DB::table('roster_permission_role')->insert([
        'role_id' => $role->id,
        'permission_id' => PermissionModel::query()->where('name', $permission)->value('id'),
    ]);

    return $role;
}

it('renames an old permission in place when Guardian\'s does not exist yet, and the admin passes the check', function (): void {
    authorizationOn();
    PermissionModel::query()->where('name', 'like', 'guardian.%')->delete();

    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    grant($ada, 'admin', $acme);
    $oldId = PermissionModel::query()->where('name', 'roster.sso.manage')->value('id');

    expect(heldSsoScimPermissions(adminRole()))->toBe(['roster.scim.manage', 'roster.sso.manage', 'roster.sso.view']);

    $report = runUpgrade();

    expect($report->grants)->toBe([
        'roster.sso.view' => 'guardian.sso.view',
        'roster.sso.manage' => 'guardian.sso.manage',
        'roster.scim.manage' => 'guardian.scim.manage',
    ])
        ->and(PermissionModel::query()->where('name', 'guardian.sso.manage')->value('id'))->toBe($oldId)
        ->and(heldSsoScimPermissions(adminRole()))->toBe(['guardian.scim.manage', 'guardian.sso.manage', 'guardian.sso.view'])
        ->and(app(Authorizer::class)->check($ada, 'guardian.sso.manage', $acme))->toBeTrue();
});

it('merges the old grants into Guardian\'s permission when both exist, and drops the old row', function (): void {
    $role = customRoleHolding('roster.sso.manage');

    expect(heldSsoScimPermissions(adminRole()))->toContain('roster.sso.manage', 'guardian.sso.manage');

    runUpgrade();

    expect(PermissionModel::query()->where('name', 'like', 'roster.s%')->exists())->toBeFalse()
        ->and(heldSsoScimPermissions($role))->toBe(['guardian.sso.manage'])
        ->and(heldSsoScimPermissions(adminRole()))->toBe(['guardian.scim.manage', 'guardian.sso.manage', 'guardian.sso.view']);
});

it('changes nothing when neither name exists, or only Guardian\'s', function (): void {
    PermissionModel::query()->where('name', 'like', 'roster.s%')->delete();
    $before = DB::table('roster_permission_role')->count();

    expect(runUpgrade()->grants)->toBe([])
        ->and(DB::table('roster_permission_role')->count())->toBe($before);

    PermissionModel::query()->where('name', 'like', 'guardian.%')->delete();

    expect(runUpgrade()->grants)->toBe([])
        ->and(PermissionModel::query()->where('name', 'like', 'guardian.%')->exists())->toBeFalse();
});

it('keeps a custom role\'s grant through a failed run, and the next run completes', function (): void {
    $role = customRoleHolding('roster.sso.manage');

    $failed = false;
    DB::connection()->beforeExecuting(function (string $query) use (&$failed): void {
        if (! $failed && str_starts_with(strtolower($query), 'delete') && str_contains($query, 'roster_permissions')) {
            $failed = true;

            throw new RuntimeException('Injected failure in the grant step.');
        }
    });

    expect(fn () => runUpgrade())->toThrow(RuntimeException::class, 'Injected failure');

    expect(heldSsoScimPermissions($role))->toBe(['roster.sso.manage'])
        ->and(PermissionModel::query()->where('name', 'roster.sso.view')->exists())->toBeTrue();

    runUpgrade();

    expect(heldSsoScimPermissions($role))->toBe(['guardian.sso.manage'])
        ->and(PermissionModel::query()->where('name', 'like', 'roster.s%')->exists())->toBeFalse();
});
