<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';
require_once __DIR__.'/../Scim/helpers.php';

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Sso\Actions\SsoLoginAction;
use RefactorCircus\Guardian\Domains\Sso\Concerns\JoinsOrganizations;
use RefactorCircus\Guardian\Domains\Sso\Data\IdentityClaims;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use Workbench\App\Models\User;

// Criterion 9: Guardian joins SSO and SCIM users to an organization with its
// own helper, not Roster's internal trait, and keeps Roster's rule: a new
// member gets `roster.roles.default_member`, an existing one is left alone.

/**
 * The role slugs a user holds in an organization.
 *
 * @return array<int, string>
 */
function organizationRoles(Model $user, OrganizationModel $organization): array
{
    return RoleAssignmentModel::query()
        ->where('user_id', $user->getKey())
        ->where('organization_id', $organization->getKey())
        ->with('role')
        ->get()
        ->map(fn (RoleAssignmentModel $assignment): string => (string) $assignment->role?->slug)
        ->all();
}

it('gives a member SSO brings in the default member role', function (): void {
    $connection = acmeWithSso();

    [$user] = app(SsoLoginAction::class)->execute($connection, new IdentityClaims('sub-1', 'ada@acme.test', 'Ada'));

    expect($connection->organization->membershipFor($user)?->source)->toBe(MembershipSource::Domain->value)
        ->and(organizationRoles($user, $connection->organization))->toBe(['member']);
});

it('gives a member SCIM provisions the default member role', function (): void {
    [$acme, $this->scimToken] = scimOrg();

    scim('POST', '/Users', oktaUser())->assertCreated();

    $ada = User::query()->where('email', 'ada@acme.test')->sole();

    expect(organizationRoles($ada, $acme))->toBe(['member']);
});

it('assigns no role when the default member role is turned off', function (): void {
    config(['roster.roles.default_member' => null]);
    $connection = acmeWithSso();

    [$user] = app(SsoLoginAction::class)->execute($connection, new IdentityClaims('sub-1', 'ada@acme.test', 'Ada'));

    expect($connection->organization->membershipFor($user))->not->toBeNull()
        ->and(organizationRoles($user, $connection->organization))->toBe([]);
});

it('leaves an existing membership, its source and its roles as they are', function (): void {
    $connection = acmeWithSso();
    $ada = user(['email' => 'ada@acme.test']);
    MembershipModel::query()->create(['organization_id' => $connection->organization_id, 'user_id' => $ada->getKey(), 'source' => MembershipSource::Direct->value]);

    app(SsoLoginAction::class)->execute($connection, new IdentityClaims('sub-1', 'ada@acme.test', 'Ada'));

    expect($connection->organization->membershipFor($ada)?->source)->toBe(MembershipSource::Direct->value)
        ->and(organizationRoles($ada, $connection->organization))->toBe([]);
});

it('gives the organization\'s owner no member role', function (): void {
    $connection = acmeWithSso();
    $owner = $connection->organization->owner;
    MembershipModel::query()->where('organization_id', $connection->organization_id)->delete();
    $joiner = new class
    {
        use JoinsOrganizations;

        public function __invoke(OrganizationModel $organization, Model $user): MembershipModel
        {
            return $this->join($organization, $user, MembershipSource::Domain);
        }
    };

    $joiner($connection->organization, $owner);

    expect(organizationRoles($owner, $connection->organization))->not->toContain('member');
});

it('refuses a membership source nothing registers', function (): void {
    $connection = acmeWithSso();
    $joiner = new class
    {
        use JoinsOrganizations;

        public function __invoke(OrganizationModel $organization, Model $user): MembershipModel
        {
            return $this->join($organization, $user, 'nowhere');
        }
    };

    $joiner($connection->organization, user());
})->throws(InvalidArgumentException::class, 'The membership source [nowhere] is not registered');
