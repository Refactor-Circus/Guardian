<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Upgrade\RosterUpgrade;
use RefactorCircus\Guardian\Upgrade\UpgradeReport;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/*
 * Shared by tests/Feature/Upgrade. The suite migrates Roster, Roster's SSO
 * and SCIM tables as Roster created them at the pinned SHA (tests/Fixtures)
 * and Guardian's create migrations, so a test starts with both sets of
 * tables, all empty: the state an app reaches when Guardian was installed
 * before Roster let go.
 */

/**
 * Guardian's table keys, parents first.
 */
const UPGRADE_KEYS = ['sso_connections', 'sso_identities', 'scim_tokens', 'scim_users', 'scim_groups'];

/**
 * Roster's SSO and SCIM permissions at the pinned SHA, with their
 * descriptions.
 */
const ROSTER_PERMISSIONS = [
    'roster.sso.view' => "View an organization's single sign-on connections.",
    'roster.sso.manage' => 'Configure single sign-on connections.',
    'roster.scim.manage' => 'Issue and revoke SCIM provisioning tokens.',
];

/**
 * Roster's SSO and SCIM permissions, as Roster registered them at the pinned
 * SHA and granted them to its built-in `admin` role. A Roster that has handed
 * them over no longer creates them, so they are put back here.
 */
function seedRosterPermissions(): void
{
    $now = now();
    $admin = DB::table('roster_roles')->whereNull('organization_id')->where('slug', 'admin')->value('id');

    foreach (ROSTER_PERMISSIONS as $name => $description) {
        if (! DB::table('roster_permissions')->where('name', $name)->exists()) {
            DB::table('roster_permissions')->insert(['id' => (string) Str::ulid(), 'name' => $name, 'description' => $description, 'system' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        $permission = DB::table('roster_permissions')->where('name', $name)->value('id');

        if ($admin !== null && ! DB::table('roster_permission_role')->where(['role_id' => $admin, 'permission_id' => $permission])->exists()) {
            DB::table('roster_permission_role')->insert(['role_id' => $admin, 'permission_id' => $permission]);
        }
    }

    app(Permissions::class)->forgetCache();
}

function runUpgrade(): UpgradeReport
{
    return app(RosterUpgrade::class)->run();
}

/**
 * Drop one prefix's five tables, children first.
 */
function dropUpgradeTables(string $prefix): void
{
    foreach (array_reverse(UPGRADE_KEYS) as $key) {
        Schema::dropIfExists($prefix.'_'.$key);
    }
}

/**
 * Rows in each of one prefix's tables, null for a missing table.
 *
 * @return array<string, int|null>
 */
function upgradeRowCounts(string $prefix): array
{
    $counts = [];

    foreach (UPGRADE_KEYS as $key) {
        $counts[$key] = Schema::hasTable($prefix.'_'.$key) ? DB::table($prefix.'_'.$key)->count() : null;
    }

    return $counts;
}

/**
 * One row in each of one prefix's five tables, linked as the real ones are:
 * an enforced connection for acme.test, an identity through it, a token tied
 * to it, a provisioned user and a provisioned group.
 *
 * @return array<string, string> table key => the row's id
 */
function seedUpgradeRows(string $prefix): array
{
    $organization = organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);
    $team = TeamModel::factory()->create(['organization_id' => $organization->id]);
    $member = user(['email' => 'ada@acme.test']);
    $now = now();

    $ids = array_combine(UPGRADE_KEYS, array_map(fn (): string => (string) Str::ulid(), UPGRADE_KEYS));

    DB::table($prefix.'_sso_connections')->insert([
        'id' => $ids['sso_connections'], 'organization_id' => $organization->id, 'name' => 'Okta', 'slug' => 'acme-sso',
        'protocol' => 'oidc', 'config' => encrypt((string) json_encode(['issuer' => 'https://idp.test']), false), 'jit' => true, 'enforced' => true,
        'enabled' => true, 'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table($prefix.'_sso_identities')->insert([
        'id' => $ids['sso_identities'], 'connection_id' => $ids['sso_connections'], 'user_id' => $member->getKey(),
        'subject' => 'okta|ada', 'email' => 'ada@acme.test', 'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table($prefix.'_scim_tokens')->insert([
        'id' => $ids['scim_tokens'], 'organization_id' => $organization->id, 'name' => 'Okta',
        'token_hash' => hash('sha256', $prefix), 'sso_connection_id' => $ids['sso_connections'],
        'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table($prefix.'_scim_users')->insert([
        'id' => $ids['scim_users'], 'organization_id' => $organization->id, 'user_id' => $member->getKey(),
        'external_id' => 'okta-ada', 'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table($prefix.'_scim_groups')->insert([
        'id' => $ids['scim_groups'], 'organization_id' => $organization->id, 'team_id' => $team->id,
        'external_id' => 'okta-team', 'created_at' => $now, 'updated_at' => $now,
    ]);

    return $ids;
}
