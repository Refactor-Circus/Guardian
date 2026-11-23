<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2).'/Fixtures/roster-role-tables.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Upgrade\AdoptionReport;
use RefactorCircus\Guardian\Upgrade\RosterRoleAdoption;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

// Criteria 20 and 21: Roster v0.1's role tables copied into Guardian's.

function adopt(): AdoptionReport
{
    return app(RosterRoleAdoption::class)->run();
}

/**
 * Every row of Roster's four role tables, to prove they are untouched.
 *
 * @return array<string, list<array<string, mixed>>>
 */
function legacyRosterRows(): array
{
    $rows = [];

    foreach (['permissions', 'roles', 'permission_role', 'role_assignments'] as $key) {
        $rows[$key] = DB::table(legacyRosterTable($key))->get()->map(fn (object $row): array => (array) $row)->sortBy(fn (array $row): string => (string) json_encode($row))->values()->all();
    }

    return $rows;
}

it('copies Roster\'s roles, keeping their ULIDs and mapping scopes, owners and permission names', function (): void {
    $world = seedRosterRoleDataset();

    $report = adopt();

    expect($report->outcome)->toBe(AdoptionReport::ADOPTED)
        ->and($report->copied)->toBe(['permissions' => 2, 'roles' => 6, 'grants' => 9, 'assignments' => 7])
        ->and($report->renamed)->toBe([
            'roster.roles.assign' => 'guardian.roles.assign',
            'roster.roles.manage' => 'guardian.roles.manage',
            'roster.roles.view' => 'guardian.roles.view',
            'roster.scim.manage' => 'guardian.scim.manage',
            'roster.sso.view' => 'guardian.sso.view',
        ]);

    $organization = (new OrganizationModel)->getMorphClass();
    $team = (new TeamModel)->getMorphClass();

    $roles = RoleModel::query()->get()->keyBy('slug');

    expect($roles->keys()->sort()->values()->all())->toBe(['acme-billing', 'admin', 'auditor', 'lead', 'member', 'super-admin'])
        ->and($roles['admin']->id)->toBe($world['roles']['admin'])
        ->and($roles['super-admin']->scope)->toBe('global')
        ->and($roles['super-admin']->super)->toBeTrue()
        ->and($roles['admin']->scope)->toBe($organization)
        ->and($roles['lead']->scope)->toBe($team)
        ->and($roles['acme-billing']->owner_type)->toBe($organization)
        ->and($roles['acme-billing']->owner_id)->toBe((string) $world['scopes']['acme']?->getKey())
        ->and($roles['admin']->permissions->pluck('name')->sort()->values()->all())->toBe(['guardian.roles.assign', 'guardian.roles.view', 'guardian.scim.manage', 'guardian.sso.view'])
        ->and(PermissionModel::query()->whereIn('name', array_keys(RosterRoleAdoption::PERMISSIONS))->exists())->toBeFalse();

    $lead = RoleAssignmentModel::query()->where('role_id', $roles['lead']->id)->sole();

    expect($lead->scope_type)->toBe($team)
        ->and($lead->scope_id)->toBe((string) $world['scopes']['red']?->getKey())
        ->and(RoleAssignmentModel::query()->where('role_id', $roles['super-admin']->id)->sole()->scope_type)->toBeNull()
        ->and(RoleAssignmentModel::query()->where('role_id', $roles['acme-billing']->id)->sole()->scope_type)->toBe($organization)
        ->and(RoleAssignmentModel::query()->pluck('id')->sort()->values()->all())
        ->toBe(DB::table(legacyRosterTable('role_assignments'))->pluck('id')->map(strval(...))->sort()->values()->all());
});

it('answers every permission in every scope as Roster did', function (): void {
    $world = seedRosterRoleDataset();

    adopt();

    $permissions = app(Permissions::class);
    $answers = [];

    foreach (ROSTER_ADOPTION_EXPECTED as $user => $scopes) {
        foreach ($scopes as $scope => $allowed) {
            foreach (ROSTER_ADOPTION_PERMISSIONS as $permission) {
                $answers[$user][$scope][$permission] = $permissions->allows($world['users'][$user], $permission, $world['scopes'][$scope]);
            }
        }
    }

    $expected = [];

    foreach (ROSTER_ADOPTION_EXPECTED as $user => $scopes) {
        foreach ($scopes as $scope => $allowed) {
            foreach (ROSTER_ADOPTION_PERMISSIONS as $permission) {
                $expected[$user][$scope][$permission] = in_array($permission, $allowed, true);
            }
        }
    }

    expect($answers)->toBe($expected);
});

it('changes nothing the second time, and says it is already adopted', function (): void {
    seedRosterRoleDataset();
    adopt();
    $before = [RoleModel::query()->count(), RoleAssignmentModel::query()->count(), PermissionModel::query()->count()];

    $report = adopt();

    expect($report->outcome)->toBe(AdoptionReport::ALREADY)
        ->and($report->lines()[0])->toContain('already adopted')
        ->and([RoleModel::query()->count(), RoleAssignmentModel::query()->count(), PermissionModel::query()->count()])->toBe($before);
});

it('has nothing to adopt without Roster\'s role tables', function (): void {
    $seeded = RoleModel::query()->pluck('id')->sort()->values()->all();

    $report = adopt();

    expect($report->outcome)->toBe(AdoptionReport::NOTHING)
        ->and($report->lines()[0])->toContain('nothing to adopt')
        ->and(RoleModel::query()->pluck('id')->sort()->values()->all())->toBe($seeded);
});

it('changes nothing while Guardian holds an assignment or a role of its own', function (string $state): void {
    seedRosterRoleDataset();

    match ($state) {
        'assignment' => grant(user(), 'member'),
        'custom role' => RoleModel::query()->create(['name' => 'Ops', 'slug' => 'ops', 'scope' => RoleModel::ANY]),
    };

    $before = RoleModel::query()->pluck('id')->sort()->values()->all();

    $report = adopt();

    expect($report->outcome)->toBe(AdoptionReport::GUARDIAN_HOLDS_ROLES)
        ->and($report->lines()[0])->toContain('nothing adopted')
        ->and(RoleModel::query()->pluck('id')->sort()->values()->all())->toBe($before)
        ->and(RoleModel::query()->where('slug', 'auditor')->exists())->toBeFalse();
})->with(['assignment', 'custom role']);

it('never alters or drops Roster\'s tables', function (): void {
    seedRosterRoleDataset();
    $before = legacyRosterRows();

    adopt();
    adopt();

    expect(legacyRosterRows())->toBe($before);
});

it('honours custom guardian.adopt table names and scope types', function (): void {
    config([
        'roster.tables.permissions' => 'old_permissions',
        'roster.tables.roles' => 'old_roles',
        'roster.tables.permission_role' => 'old_permission_role',
        'roster.tables.role_assignments' => 'old_role_assignments',
        'guardian.adopt.roster_tables' => [
            'permissions' => 'old_permissions',
            'roles' => 'old_roles',
            'permission_role' => 'old_permission_role',
            'role_assignments' => 'old_role_assignments',
        ],
        'guardian.adopt.scope_types' => ['organization' => 'org', 'team' => 'squad'],
    ]);
    seedRosterRoleDataset();

    $report = adopt();

    expect($report->outcome)->toBe(AdoptionReport::ADOPTED)
        ->and($report->scopeTypes)->toBe(['organization' => 'org', 'team' => 'squad'])
        ->and(RoleModel::query()->where('slug', 'lead')->sole()->scope)->toBe('squad')
        ->and(RoleAssignmentModel::query()->whereNotNull('scope_type')->pluck('scope_type')->unique()->sort()->values()->all())->toBe(['org', 'squad']);
});

it('logs the report from the migration and prints it from the command', function (): void {
    seedRosterRoleDataset();
    Log::spy();

    (require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000003_adopt_roster_role_tables.php')->up();

    Log::shouldHaveReceived('info')->withArgs(fn (string $line): bool => str_starts_with($line, 'Guardian: Roles: adopted 6 roles'));

    $this->artisan('guardian:adopt-roster-roles')
        ->expectsOutputToContain('already adopted')
        ->assertSuccessful();
});

it('prints that there is nothing to adopt when Roster\'s tables are not there', function (): void {
    $this->artisan('guardian:adopt-roster-roles')
        ->expectsOutputToContain('nothing to adopt')
        ->assertSuccessful();
});
