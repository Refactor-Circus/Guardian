<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;

/**
 * Makes a user a member of an organization when SSO or SCIM brings them in.
 *
 * Roster's own join is a trait its domains share among themselves, outside
 * the public API Guardian may use (criterion 9), and no Roster action joins
 * with a given source without refusing an existing member. So Guardian keeps
 * the same rule here, on Roster's models and its `MembershipSources` seam:
 * a new member gets the default member role from `roster.roles.default_member`,
 * through Keystone's bound `Roles` (whichever role store is installed); an
 * existing membership is left as it is; owners need no role.
 */
trait JoinsOrganizations
{
    /**
     * @param  MembershipSource|string  $source  a built-in source, or one registered with `MembershipSources`
     *
     * @throws InvalidArgumentException for a source nothing registers
     */
    private function join(OrganizationModel $organization, Model $user, MembershipSource|string $source): MembershipModel
    {
        if (is_string($source) && ! app(MembershipSources::class)->has($source)) {
            throw new InvalidArgumentException("The membership source [{$source}] is not registered: register it with MembershipSources::register().");
        }

        $membership = MembershipModel::query()->firstOrCreate(
            ['organization_id' => $organization->getKey(), 'user_id' => $user->getKey()],
            ['source' => $source instanceof MembershipSource ? $source->value : $source],
        );

        if ($membership->wasRecentlyCreated && ! $organization->isOwnedBy($user)) {
            $this->assignDefaultRole($organization, $user);
        }

        return $membership;
    }

    /**
     * Skipped when the setting is empty or the role store does not know the
     * role in the organization (none is installed, or it has no such role).
     */
    private function assignDefaultRole(OrganizationModel $organization, Model $user): void
    {
        $slug = config('roster.roles.default_member');

        if (! is_string($slug) || $slug === '' || ! $user instanceof Authenticatable) {
            return;
        }

        $roles = app(Roles::class);

        if (! $roles->exists($slug, $organization)) {
            return;
        }

        $roles->assign($user, $slug, $organization);

        app(Permissions::class)->forget($user);
    }
}
