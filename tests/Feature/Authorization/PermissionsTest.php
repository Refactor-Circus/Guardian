<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Role\Support\BuiltInRoles;

// Criterion 10: the SSO domain registers its resource and permissions
// through Roster's public seams, and its policy answers them.

const SSO_PERMISSIONS = [
    'guardian.sso.manage' => 'Configure single sign-on connections.',
    'guardian.sso.view' => "View an organization's single sign-on connections.",
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

it('registers the guardian.sso resource on Roster\'s registry', function (): void {
    expect(app(Resources::class)->model('guardian.sso'))->toBe(SsoConnectionModel::class)
        ->and(Gate::getPolicyFor(SsoConnectionModel::class))->toBeInstanceOf(SsoConnectionPolicy::class);
});

it('registers the SSO permissions for admins on Roster\'s catalogue', function (): void {
    $catalogue = app(PermissionCatalogue::class);

    foreach (SSO_PERMISSIONS as $permission => $description) {
        expect($catalogue->has($permission))->toBeTrue()
            ->and($catalogue->all()[$permission])->toBe(['description' => $description, 'grantTo' => ['admin']]);
    }
});

it('creates the SSO permissions as system permissions, held by a newly created admin role', function (): void {
    PermissionModel::query()->whereIn('name', array_keys(SSO_PERMISSIONS))->delete();
    RoleModel::query()->whereNull('organization_id')->where('slug', 'admin')->delete();

    BuiltInRoles::sync();

    $admin = RoleModel::query()->whereNull('organization_id')->where('slug', 'admin')->sole();

    expect(PermissionModel::query()->whereIn('name', array_keys(SSO_PERMISSIONS))->where('system', true)->orderBy('name')->pluck('description', 'name')->all())
        ->toBe(SSO_PERMISSIONS)
        ->and($admin->permissions()->pluck('name')->all())->toContain(...array_keys(SSO_PERMISSIONS));
});

it('answers guardian.sso.manage through the connection policy, per organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $ada = user();
    grant($ada, 'admin', $acme);

    $authorizer = app(Authorizer::class);

    expect($authorizer->check($ada, 'guardian.sso.manage', $acme))->toBeTrue()
        ->and($authorizer->check($ada, 'guardian.sso.manage', $globex))->toBeFalse();

    Gate::policy(SsoConnectionModel::class, RefusingSsoConnectionPolicy::class);
    // Remembered answers go when Permissions forgets anything.
    app(Permissions::class)->flush();

    expect($authorizer->check($ada, 'guardian.sso.manage', $acme))->toBeFalse();
});

it('names no roster.sso or roster.scim permission outside the upgrade', function (): void {
    $root = dirname(__DIR__, 3);
    $offenders = [];

    foreach ([...phpFiles($root.'/src'), ...phpFiles($root.'/config'), ...phpFiles($root.'/resources')] as $file) {
        if (str_starts_with($file, $root.'/src/Upgrade/')) {
            continue;
        }

        if (preg_match('/roster\.(sso|scim)\b/', (string) file_get_contents($file)) === 1) {
            $offenders[] = substr($file, strlen($root) + 1);
        }
    }

    expect($offenders)->toBe([]);
});
