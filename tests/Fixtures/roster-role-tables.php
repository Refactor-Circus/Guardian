<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use Workbench\App\Models\User;

/*
 * Roster v0.1's role tables (tests/Fixtures/roster-roles) filled with a
 * dataset for the adoption (criteria 20 and 21): global, organization, team,
 * an organization's own custom role and a super role, and the answers
 * Roster v0.1's `Permissions::for()` gave for it.
 *
 * Roster v0.1's rules, which ROSTER_ADOPTION_EXPECTED records: a super-admin
 * holds everything; a global assignment applies everywhere; an organization
 * assignment (no team) applies in the organization and on each of its teams;
 * a team assignment (stored with its organization and team) only on that
 * team. Organization owners (who hold everything in their organization
 * through the owner rule, in both packages) are left out of the dataset.
 */

/**
 * The permissions the answers are checked for, by Guardian's names.
 */
const ROSTER_ADOPTION_PERMISSIONS = [
    'guardian.roles.view',
    'guardian.roles.manage',
    'guardian.roles.assign',
    'guardian.sso.view',
    'guardian.scim.manage',
    'invoices.edit',
    'reports.view',
];

/**
 * user => scope => the permissions Roster v0.1 allowed there (the rest it
 * refused). Scopes: `global`, organizations `acme` and `globex`, and `red`,
 * a team of acme.
 */
const ROSTER_ADOPTION_EXPECTED = [
    // super-admin, globally
    'ada' => [
        'global' => ROSTER_ADOPTION_PERMISSIONS,
        'acme' => ROSTER_ADOPTION_PERMISSIONS,
        'red' => ROSTER_ADOPTION_PERMISSIONS,
        'globex' => ROSTER_ADOPTION_PERMISSIONS,
    ],
    // admin in acme
    'bob' => [
        'global' => [],
        'acme' => ['guardian.roles.view', 'guardian.roles.assign', 'guardian.sso.view', 'guardian.scim.manage'],
        'red' => ['guardian.roles.view', 'guardian.roles.assign', 'guardian.sso.view', 'guardian.scim.manage'],
        'globex' => [],
    ],
    // member in acme, lead on red
    'cat' => [
        'global' => [],
        'acme' => ['reports.view'],
        'red' => ['invoices.edit', 'reports.view'],
        'globex' => [],
    ],
    // auditor (a custom global role), globally
    'dan' => [
        'global' => ['guardian.roles.view', 'reports.view'],
        'acme' => ['guardian.roles.view', 'reports.view'],
        'red' => ['guardian.roles.view', 'reports.view'],
        'globex' => ['guardian.roles.view', 'reports.view'],
    ],
    // acme's own billing role, in acme
    'eve' => [
        'global' => [],
        'acme' => ['invoices.edit'],
        'red' => ['invoices.edit'],
        'globex' => [],
    ],
    // member in globex
    'fay' => [
        'global' => [],
        'acme' => [],
        'red' => [],
        'globex' => ['reports.view'],
    ],
];

/**
 * A Roster role table by key, as the fixture migration names it.
 */
function legacyRosterTable(string $key): string
{
    $table = config("roster.tables.{$key}");

    return is_string($table) && $table !== '' ? $table : 'roster_'.$key;
}

/**
 * Roster v0.1's role tables with its built-in roles, made once.
 */
function createLegacyRosterRoleTables(): void
{
    if (! Schema::hasTable(legacyRosterTable('roles'))) {
        (require __DIR__.'/roster-roles/2026_01_01_000003_create_legacy_roster_role_tables.php')->up();
    }
}

/**
 * The dataset in Roster's tables, and what a test needs to ask about it.
 *
 * @return array{users: array<string, User>, scopes: array<string, OrganizationModel|TeamModel|null>, roles: array<string, string>}
 */
function seedRosterRoleDataset(): array
{
    // Creating an organization would assign its owner Guardian's default
    // member role, so Guardian would already hold an assignment.
    config(['roster.roles.default_member' => null]);

    createLegacyRosterRoleTables();

    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $red = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Red'));
    $now = now();

    $permissions = [];

    foreach (['roster.roles.view', 'roster.roles.manage', 'roster.roles.assign', 'roster.sso.view', 'roster.scim.manage', 'invoices.edit', 'reports.view'] as $name) {
        $permissions[$name] = (string) Str::ulid();
        DB::table(legacyRosterTable('permissions'))->insert(['id' => $permissions[$name], 'name' => $name, 'system' => str_starts_with($name, 'roster.'), 'created_at' => $now, 'updated_at' => $now]);
    }

    $roles = DB::table(legacyRosterTable('roles'))->pluck('id', 'slug')->map(strval(...))->all();

    foreach ([
        'auditor' => ['Auditor', 'global', null],
        'acme-billing' => ['Billing', 'organization', $acme->getKey()],
    ] as $slug => [$name, $scope, $organization]) {
        $roles[$slug] = (string) Str::ulid();
        DB::table(legacyRosterTable('roles'))->insert(['id' => $roles[$slug], 'name' => $name, 'slug' => $slug, 'scope' => $scope, 'organization_id' => $organization, 'super' => false, 'system' => false, 'created_at' => $now, 'updated_at' => $now]);
    }

    foreach ([
        'admin' => ['roster.roles.view', 'roster.roles.assign', 'roster.sso.view', 'roster.scim.manage'],
        'member' => ['reports.view'],
        'lead' => ['invoices.edit'],
        'auditor' => ['roster.roles.view', 'reports.view'],
        'acme-billing' => ['invoices.edit'],
    ] as $slug => $names) {
        foreach ($names as $name) {
            DB::table(legacyRosterTable('permission_role'))->insert(['role_id' => $roles[$slug], 'permission_id' => $permissions[$name]]);
        }
    }

    $users = [];

    foreach (['ada', 'bob', 'cat', 'dan', 'eve', 'fay'] as $name) {
        $users[$name] = user(['email' => $name.'@example.com']);
    }

    foreach ([
        ['ada', 'super-admin', null, null],
        ['bob', 'admin', $acme, null],
        ['cat', 'member', $acme, null],
        ['cat', 'lead', $acme, $red],
        ['dan', 'auditor', null, null],
        ['eve', 'acme-billing', $acme, null],
        ['fay', 'member', $globex, null],
    ] as [$user, $role, $organization, $team]) {
        DB::table(legacyRosterTable('role_assignments'))->insert([
            'id' => (string) Str::ulid(),
            'role_id' => $roles[$role],
            'user_id' => $users[$user]->getKey(),
            'organization_id' => $organization?->getKey(),
            'team_id' => $team?->getKey(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    return [
        'users' => $users,
        'scopes' => ['global' => null, 'acme' => $acme, 'red' => $red, 'globex' => $globex],
        'roles' => $roles,
    ];
}
