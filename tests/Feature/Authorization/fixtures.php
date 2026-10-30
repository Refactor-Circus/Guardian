<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;

/**
 * The SSO cases of Roster's tests/Authorization/fixtures.php at the pinned
 * SHA (tests/moved-tests.json), with Guardian's route names: an organization
 * `acme` with a member `target`, a connection `acme-okta` and the target's
 * identity on it.
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

    return compact('acme', 'target', 'connection', 'identity');
}

/**
 * Turn Roster's authorization back on, as Roster's AuthorizationTestCase
 * does: the base TestCase turns it off for surface tests.
 */
function authorizationOn(): void
{
    config(['roster.authorization' => true]);
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
    ];
}
