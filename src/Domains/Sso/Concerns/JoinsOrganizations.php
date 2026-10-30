<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

/**
 * Makes a user a member of an organization when SSO or SCIM brings them in.
 *
 * Roster's own join is a trait its domains share among themselves, outside
 * the public API Guardian may use (criterion 9), and no Roster action joins
 * with a given source without refusing an existing member. So Guardian keeps
 * the same rule here, on Roster's models and its `MembershipSources` seam:
 * a new member gets the default member role from `roster.roles.default_member`;
 * an existing membership is left as it is; owners need no role.
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

    private function assignDefaultRole(OrganizationModel $organization, Model $user): void
    {
        $slug = config('roster.roles.default_member');

        if (! is_string($slug) || $slug === '') {
            return;
        }

        $role = RoleModel::query()
            ->where('scope', RoleScope::Organization)
            ->where('slug', $slug)
            ->where(fn (Builder $query): Builder => $query->whereNull('organization_id')->orWhere('organization_id', $organization->getKey()))
            ->orderByRaw('organization_id is null')
            ->first();

        if ($role === null) {
            return;
        }

        RoleAssignmentModel::query()->firstOrCreate([
            'role_id' => $role->getKey(),
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'team_id' => null,
        ]);

        app(Permissions::class)->flushUser($user);
    }
}
