<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;

/**
 * The SSO and SCIM cases of Roster's tests/Authorization/fixtures.php at the
 * pinned SHA (tests/moved-tests.json), with Guardian's route names: an
 * organization `acme` with a member `target`, a connection `acme-okta`, the
 * target's identity on it and a SCIM token; plus a shared role `editor`,
 * held by the target.
 *
 * @return array<string, mixed>
 */
function authorizationWorld(): array
{
    $acme = organization(attributes: ['name' => 'Acme']);
    $target = user(['email' => 'target@example.com']);
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $target->getRouteKey()));

    $connection = SsoConnectionModel::factory()->create(['organization_id' => $acme->id, 'slug' => 'acme-okta']);
    $identity = SsoIdentityModel::query()->create(['connection_id' => $connection->id, 'user_id' => $target->getKey(), 'subject' => 'sub-1']);

    $scimToken = ScimTokenModel::query()->create(['organization_id' => $acme->id, 'name' => 'Okta', 'token_hash' => hash('sha256', 'x')]);

    $role = RoleModel::query()->create(['name' => 'Editor', 'slug' => 'editor', 'scope' => RoleModel::ANY]);
    $assignment = RoleAssignmentModel::query()->create(['role_id' => $role->id, 'user_id' => $target->getKey()]);

    return compact('acme', 'target', 'connection', 'identity', 'scimToken', 'role', 'assignment');
}

/**
 * Turn Guardian's authorization back on: the base TestCase turns it off for
 * surface tests.
 */
function authorizationOn(): void
{
    config(['guardian.authorization.enabled' => true]);
}

/**
 * Every Guardian JSON API route: [method, name, parameters, body].
 *
 * @return array<string, array{0: string, 1: string, 2: Closure(array<string, mixed>): array<int, mixed>, 3: array<string, mixed>}>
 */
function apiRoutes(): array
{
    $user = fn (array $w): array => [$w['target']->getRouteKey()];
    $org = fn (): array => ['acme'];

    return [
        'sso-connections.index' => ['GET', 'guardian.organizations.sso-connections.index', $org, []],
        'sso-connections.store' => ['POST', 'guardian.organizations.sso-connections.store', $org, ['name' => 'X', 'protocol' => 'oidc']],
        'sso-connections.show' => ['GET', 'guardian.sso-connections.show', fn (): array => ['acme-okta'], []],
        'sso-connections.update' => ['PATCH', 'guardian.sso-connections.update', fn (): array => ['acme-okta'], ['name' => 'Y']],
        'sso-connections.destroy' => ['DELETE', 'guardian.sso-connections.destroy', fn (): array => ['acme-okta'], []],
        'users.sso-identities.index' => ['GET', 'guardian.users.sso-identities.index', $user, []],
        'sso-identities.destroy' => ['DELETE', 'guardian.sso-identities.destroy', fn (array $w): array => [$w['identity']->id], []],
        'scim-tokens.index' => ['GET', 'guardian.organizations.scim-tokens.index', $org, []],
        'scim-tokens.store' => ['POST', 'guardian.organizations.scim-tokens.store', $org, ['name' => 'Okta']],
        'roles.index' => ['GET', 'guardian.roles.index', fn (): array => [], []],
        'roles.store' => ['POST', 'guardian.roles.store', fn (): array => [], ['name' => 'X']],
        'roles.show' => ['GET', 'guardian.roles.show', fn (): array => ['editor'], []],
        'roles.update' => ['PATCH', 'guardian.roles.update', fn (): array => ['editor'], ['name' => 'Y']],
        'roles.destroy' => ['DELETE', 'guardian.roles.destroy', fn (): array => ['editor'], []],
        'role-assignments.index' => ['GET', 'guardian.role-assignments.index', fn (): array => [], []],
        'role-assignments.store' => ['POST', 'guardian.role-assignments.store', fn (): array => [], ['user' => 'x', 'role' => 'member']],
        'role-assignments.destroy' => ['DELETE', 'guardian.role-assignments.destroy', fn (array $w): array => [$w['assignment']->id], []],
        'users.abilities.index' => ['GET', 'guardian.users.abilities.index', $user, []],
        'scim-tokens.destroy' => ['DELETE', 'guardian.scim-tokens.destroy', fn (array $w): array => [$w['scimToken']->id], []],
    ];
}
