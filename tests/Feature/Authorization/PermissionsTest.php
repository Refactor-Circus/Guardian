<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Policies\ScimTokenPolicy;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Keystone\Authorization\Abilities;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Testing\FakePermissions;

// Criterion 5: the SSO and SCIM abilities are defined in Keystone's
// Abilities and checked through Keystone's bound Permissions, in the Roster
// organization, where the organization's owner rule still applies through
// Roster's Scope. Granted here with Keystone's fakes (TestCase, grant()).

const SSO_PERMISSIONS = [
    'guardian.sso.manage' => 'Configure single sign-on connections.',
    'guardian.sso.view' => "View an organization's single sign-on connections.",
];

const SCIM_PERMISSIONS = [
    'guardian.scim.manage' => 'Issue and revoke SCIM provisioning tokens.',
];

/**
 * A policy that refuses everything, to prove the check reaches the policy.
 */
final class RefusingSsoConnectionPolicy
{
    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return false;
    }
}

beforeEach(fn () => authorizationOn());

it('defines the SSO and SCIM abilities in Keystone\'s Abilities, granted to admin', function (): void {
    $abilities = app(Abilities::class)->all();

    foreach ([...SSO_PERMISSIONS, ...SCIM_PERMISSIONS] as $ability => $description) {
        expect($abilities[$ability] ?? null)->toBe(['description' => $description, 'grantTo' => ['admin']]);
    }
});

it('answers the SSO and SCIM abilities with the connection and token policies', function (): void {
    expect(Gate::getPolicyFor(SsoConnectionModel::class))->toBeInstanceOf(SsoConnectionPolicy::class)
        ->and(Gate::getPolicyFor(ScimTokenModel::class))->toBeInstanceOf(ScimTokenPolicy::class);
});

it('answers guardian.scim.manage per organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $ada = user();
    grant($ada, 'admin', $acme);

    $authorization = app(Authorization::class);

    expect($authorization->check($ada, 'guardian.scim.manage', $acme))->toBeTrue()
        ->and($authorization->check($ada, 'guardian.scim.manage', $globex))->toBeFalse();
});

it('answers through Keystone\'s bound Permissions, granted directly with FakePermissions', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $ada = user();

    $permissions = app(Permissions::class);
    expect($permissions)->toBeInstanceOf(FakePermissions::class);
    assert($permissions instanceof FakePermissions);
    $permissions->grant($ada, 'guardian.sso.view', $acme);

    $authorization = app(Authorization::class);

    expect($authorization->check($ada, 'guardian.sso.view', $acme))->toBeTrue()
        ->and($authorization->check($ada, 'guardian.sso.view', $globex))->toBeFalse()
        ->and($authorization->check($ada, 'guardian.sso.manage', $acme))->toBeFalse()
        ->and($authorization->check(null, 'guardian.sso.view', $acme))->toBeFalse();
});

it('lets an organization\'s owner use the abilities there, through Roster\'s Scope', function (): void {
    $owner = user();
    $acme = organization($owner, ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);

    $authorization = app(Authorization::class);

    expect($authorization->check($owner, 'guardian.sso.manage', $acme))->toBeTrue()
        ->and($authorization->check($owner, 'guardian.scim.manage', $acme))->toBeTrue()
        ->and($authorization->check($owner, 'guardian.sso.manage', $globex))->toBeFalse();
});

it('answers guardian.sso.manage through the connection policy, per organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $ada = user();
    grant($ada, 'admin', $acme);

    $authorization = app(Authorization::class);

    expect($authorization->check($ada, 'guardian.sso.manage', $acme))->toBeTrue()
        ->and($authorization->check($ada, 'guardian.sso.manage', $globex))->toBeFalse();

    Gate::policy(SsoConnectionModel::class, RefusingSsoConnectionPolicy::class);

    expect($authorization->check($ada, 'guardian.sso.manage', $acme))->toBeFalse();
});

it('lets everything through with guardian.authorization.enabled off', function (): void {
    config(['guardian.authorization.enabled' => false]);

    expect(app(Authorization::class)->check(null, 'guardian.sso.manage', organization()))->toBeTrue();
});

it('names no roster.sso or roster.scim permission outside the upgrade', function (): void {
    $root = str_replace('\\', '/', dirname(__DIR__, 3));
    $offenders = [];

    foreach ([...phpFiles($root.'/src'), ...phpFiles($root.'/config'), ...phpFiles($root.'/resources')] as $file) {
        // Forward slashes on Windows too, so the prefix below matches.
        $file = str_replace('\\', '/', $file);

        if (str_starts_with($file, $root.'/src/Upgrade/')) {
            continue;
        }

        if (preg_match('/roster\.(sso|scim)\b/', (string) file_get_contents($file)) === 1) {
            $offenders[] = substr($file, strlen($root) + 1);
        }
    }

    expect($offenders)->toBe([]);
});
