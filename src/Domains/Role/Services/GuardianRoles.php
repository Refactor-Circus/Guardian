<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;

/**
 * Keystone's `Roles`, kept in Guardian's role tables (criterion 12).
 *
 * A role may be assigned with no scope when its `scope` is `global` or
 * `any`, and in a scope when its `scope` is `any` or the scope's morph type;
 * a custom role (one with an owner) only in its owner. Where a scope's own
 * role and a shared one share a slug, the scope's own is used.
 */
final class GuardianRoles implements Roles
{
    public function __construct(
        private readonly GuardianPermissions $permissions,
        private readonly Users $users,
        private readonly Repository $config,
    ) {}

    public function available(): bool
    {
        return true;
    }

    public function exists(string $role, ?Model $scope = null): bool
    {
        return $this->find($role, $scope) !== null;
    }

    public function assign(Authenticatable $user, string $role, ?Model $scope = null): bool
    {
        $found = $this->find($role, $scope);

        if ($found === null) {
            return false;
        }

        RoleAssignmentModel::query()->firstOrCreate([
            'role_id' => $found->id,
            'user_id' => $user->getAuthIdentifier(),
            'scope_type' => $scope?->getMorphClass(),
            'scope_id' => $scope === null ? null : $this->key($scope),
        ]);

        $this->permissions->forget($user);

        return true;
    }

    public function revoke(Authenticatable $user, ?Model $scope = null): void
    {
        $this->assignments($user, $scope)->get()->each(fn (RoleAssignmentModel $assignment): ?bool => $assignment->delete());

        $this->permissions->forget($user);
    }

    public function revokeEverywhere(Authenticatable $user): void
    {
        RoleAssignmentModel::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->get()
            ->each(fn (RoleAssignmentModel $assignment): ?bool => $assignment->delete());

        $this->permissions->forget($user);
    }

    public function of(Authenticatable $user, ?Model $scope = null): array
    {
        $slugs = array_values(array_unique(RoleModel::query()
            ->whereIn('id', $this->assignments($user, $scope)->select('role_id'))
            ->pluck('slug')
            ->map(strval(...))
            ->all()));
        sort($slugs);

        return $slugs;
    }

    public function holders(string $ability): array
    {
        $roles = RoleModel::query()
            ->where('super', true)
            ->orWhereHas('permissions', fn (Builder $query): Builder => $query->where('name', $ability))
            ->select('id');

        $holders = RoleAssignmentModel::query()
            ->whereNull('scope_type')
            ->whereIn('role_id', $roles)
            ->distinct()
            ->pluck('user_id')
            ->all();

        foreach ($this->configuredSuperAdmins() as $user) {
            $holders[] = $user->getKey();
        }

        return array_values(array_unique(array_filter(
            $holders,
            fn (mixed $key): bool => is_int($key) || is_string($key),
        ), SORT_REGULAR));
    }

    /**
     * The role with that slug that may be assigned there, the scope's own
     * first.
     */
    private function find(string $slug, ?Model $scope): ?RoleModel
    {
        $query = RoleModel::query()->where('slug', $slug);

        if ($scope === null) {
            return $query->whereIn('scope', [RoleModel::GLOBAL, RoleModel::ANY])->whereNull('owner_type')->first();
        }

        $type = $scope->getMorphClass();
        $key = $this->key($scope);

        return $query
            ->whereIn('scope', [RoleModel::ANY, $type])
            ->where(fn (Builder $owner): Builder => $owner
                ->whereNull('owner_type')
                ->orWhere(fn (Builder $own): Builder => $own->where('owner_type', $type)->where('owner_id', $key)))
            ->orderByRaw('case when owner_type is null then 1 else 0 end')
            ->first();
    }

    /**
     * @return Builder<RoleAssignmentModel>
     */
    private function assignments(Authenticatable $user, ?Model $scope): Builder
    {
        $query = RoleAssignmentModel::query()->where('user_id', $user->getAuthIdentifier());

        return $scope === null
            ? $query->whereNull('scope_type')->whereNull('scope_id')
            : $query->where('scope_type', $scope->getMorphClass())->where('scope_id', $this->key($scope));
    }

    /**
     * The configured super-admins with a verified email.
     *
     * @return list<Model>
     */
    private function configuredSuperAdmins(): array
    {
        $found = [];

        foreach ((array) $this->config->get('guardian.super_admins', []) as $email) {
            $user = is_string($email) ? $this->users->findByEmail($email) : null;

            if ($user !== null && $this->users->emailVerified($user)) {
                $found[] = $user;
            }
        }

        return $found;
    }

    private function key(Model $scope): string
    {
        $key = $scope->getKey();

        return is_scalar($key) ? (string) $key : '';
    }
}
