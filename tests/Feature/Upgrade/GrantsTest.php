<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Criterion 22, grants: Roster's SSO and SCIM permissions become Guardian's
 * in Roster's (v0.1) role tables, and every role keeps what it held. One
 * transaction, so a failed run leaves the old state and the next run
 * completes. Roster v2 keeps no roles, so the tables are Roster v0.1's
 * (tests/Fixtures/roster-roles); Guardian's own role tables adopt them.
 */

beforeEach(function (): void {
    seedRosterPermissions();
});

/**
 * The permission names a role holds, among the ones the upgrade moves.
 *
 * @return list<string>
 */
function heldSsoScimPermissions(string $role): array
{
    return DB::table('roster_permission_role')
        ->join('roster_permissions', 'roster_permissions.id', '=', 'roster_permission_role.permission_id')
        ->where('roster_permission_role.role_id', $role)
        ->where(fn ($query) => $query->where('roster_permissions.name', 'like', 'roster.s%')->orWhere('roster_permissions.name', 'like', 'guardian.%'))
        ->orderBy('roster_permissions.name')
        ->pluck('roster_permissions.name')
        ->map(strval(...))
        ->all();
}

function adminRole(): string
{
    return (string) DB::table('roster_roles')->whereNull('organization_id')->where('slug', 'admin')->value('id');
}

function permissionId(string $name): ?string
{
    $id = DB::table('roster_permissions')->where('name', $name)->value('id');

    return is_string($id) ? $id : null;
}

/**
 * A permission row in Roster's table, granted to the admin role.
 */
function rosterPermission(string $name): void
{
    $id = (string) Str::ulid();

    DB::table('roster_permissions')->insert(['id' => $id, 'name' => $name, 'system' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('roster_permission_role')->insert(['role_id' => adminRole(), 'permission_id' => $id]);
}

/**
 * A custom role holding one permission, by name.
 */
function customRoleHolding(string $permission): string
{
    $role = (string) Str::ulid();

    DB::table('roster_roles')->insert(['id' => $role, 'name' => 'Identity', 'slug' => 'identity', 'scope' => 'organization', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('roster_permission_role')->insert(['role_id' => $role, 'permission_id' => permissionId($permission)]);

    return $role;
}

it('renames an old permission in place when Guardian\'s does not exist yet', function (): void {
    $oldId = permissionId('roster.sso.manage');

    expect(heldSsoScimPermissions(adminRole()))->toBe(['roster.scim.manage', 'roster.sso.manage', 'roster.sso.view']);

    $report = runUpgrade();

    expect($report->grants)->toBe([
        'roster.sso.view' => 'guardian.sso.view',
        'roster.sso.manage' => 'guardian.sso.manage',
        'roster.scim.manage' => 'guardian.scim.manage',
    ])
        ->and(permissionId('guardian.sso.manage'))->toBe($oldId)
        ->and(heldSsoScimPermissions(adminRole()))->toBe(['guardian.scim.manage', 'guardian.sso.manage', 'guardian.sso.view']);
});

it('merges the old grants into Guardian\'s permission when both exist, and drops the old row', function (): void {
    foreach (['guardian.sso.view', 'guardian.sso.manage', 'guardian.scim.manage'] as $name) {
        rosterPermission($name);
    }

    $role = customRoleHolding('roster.sso.manage');

    expect(heldSsoScimPermissions(adminRole()))->toContain('roster.sso.manage', 'guardian.sso.manage');

    runUpgrade();

    expect(DB::table('roster_permissions')->where('name', 'like', 'roster.s%')->exists())->toBeFalse()
        ->and(heldSsoScimPermissions($role))->toBe(['guardian.sso.manage'])
        ->and(heldSsoScimPermissions(adminRole()))->toBe(['guardian.scim.manage', 'guardian.sso.manage', 'guardian.sso.view']);
});

it('changes nothing when neither name exists, or only Guardian\'s', function (): void {
    DB::table('roster_permissions')->where('name', 'like', 'roster.s%')->delete();
    $before = DB::table('roster_permission_role')->count();

    expect(runUpgrade()->grants)->toBe([])
        ->and(DB::table('roster_permission_role')->count())->toBe($before);

    rosterPermission('guardian.sso.manage');

    expect(runUpgrade()->grants)->toBe([])
        ->and(permissionId('guardian.sso.manage'))->not->toBeNull();
});

it('changes nothing without Roster\'s role tables', function (): void {
    foreach (['roster_role_assignments', 'roster_permission_role', 'roster_roles', 'roster_permissions'] as $table) {
        DB::getSchemaBuilder()->drop($table);
    }

    expect(runUpgrade()->grants)->toBe([]);
});

it('keeps a custom role\'s grant through a failed run, and the next run completes', function (): void {
    $role = customRoleHolding('roster.sso.manage');
    rosterPermission('guardian.sso.manage');

    $failed = false;
    DB::connection()->beforeExecuting(function (string $query) use (&$failed): void {
        if (! $failed && str_starts_with(strtolower($query), 'delete') && str_contains($query, 'roster_permissions')) {
            $failed = true;

            throw new RuntimeException('Injected failure in the grant step.');
        }
    });

    expect(fn () => runUpgrade())->toThrow(RuntimeException::class, 'Injected failure');

    expect(heldSsoScimPermissions($role))->toBe(['roster.sso.manage'])
        ->and(permissionId('roster.sso.view'))->not->toBeNull();

    runUpgrade();

    expect(heldSsoScimPermissions($role))->toBe(['guardian.sso.manage'])
        ->and(DB::table('roster_permissions')->where('name', 'like', 'roster.s%')->exists())->toBeFalse();
});
