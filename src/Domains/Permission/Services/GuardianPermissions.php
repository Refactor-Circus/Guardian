<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Scope;
use RefactorCircus\Keystone\Authorization\ScopeChain;

/**
 * Keystone's `Permissions`, answered from Guardian's role tables (criteria 10,
 * 11 and 13).
 *
 * A user's grants (each assignment's scope, its role's `super` flag and each
 * permission it grants) are loaded once per request, or from the
 * permission cache, and every question is answered in memory: a global grant
 * applies everywhere; a grant in a scope applies there and in the scopes
 * below it, walking `ScopeChain` (so a team inherits its organization's); a
 * model that is not a `Scope` counts only itself. Organization and team
 * semantics come only from the scope models.
 *
 * Bound with `scoped()`, so nothing is remembered across requests but the
 * cache.
 */
final class GuardianPermissions implements Permissions
{
    /**
     * @var list<string>|null
     */
    private ?array $known = null;

    /**
     * @var array<string, list<array{scope_type: string|null, scope_id: string|null, super: bool, permission: string|null}>>
     */
    private array $grants = [];

    /**
     * @var array<string, bool>
     */
    private array $supers = [];

    /**
     * Checks being answered right now, so a scope that asks again while it
     * is asked gets false instead of a loop.
     *
     * @var array<string, true>
     */
    private array $running = [];

    public function __construct(
        private readonly Users $users,
        private readonly Repository $config,
        private readonly PermissionCache $cache,
    ) {}

    public function allows(?Authenticatable $user, string $ability, mixed $scope = null): bool
    {
        if ($user === null) {
            return false;
        }

        $key = $this->userKey($user).'|'.$ability.'|'.$this->scopeKey($scope);

        if (isset($this->running[$key])) {
            return false;
        }

        $this->running[$key] = true;

        try {
            if ($this->isSuperAdmin($user)) {
                return true;
            }

            $chain = $this->chain($scope);

            foreach ($chain as $link) {
                if ($link instanceof Scope && $link->grantsByItself($user, $ability)) {
                    return true;
                }
            }

            if (! in_array($ability, $this->known(), true)) {
                return false;
            }

            return in_array($ability, $this->held($user, $chain), true);
        } finally {
            unset($this->running[$key]);
        }
    }

    public function scopesWith(?Authenticatable $user, string $ability, string $scopeType): ?array
    {
        if ($user === null) {
            return [];
        }

        if ($this->isSuperAdmin($user) || in_array($ability, $this->held($user, []), true)) {
            return null;
        }

        $type = $this->morphType($scopeType);
        $keys = [];

        foreach ($this->grantsOf($user) as $grant) {
            if ($grant['permission'] === $ability && $grant['scope_type'] === $type && $grant['scope_id'] !== null) {
                $keys[$grant['scope_id']] = true;
            }
        }

        return array_map(strval(...), array_keys($keys));
    }

    public function abilities(?Authenticatable $user, mixed $scope = null): array
    {
        if ($user === null) {
            return [];
        }

        if ($this->isSuperAdmin($user)) {
            return $this->known();
        }

        $known = $this->known();
        $held = array_values(array_unique(array_filter(
            $this->held($user, $this->chain($scope)),
            fn (string $ability): bool => in_array($ability, $known, true),
        )));
        sort($held);

        return $held;
    }

    public function isSuperAdmin(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->supers[$this->userKey($user)] ??= $this->hasSuperRole($user) || $this->isConfiguredSuperAdmin($user);
    }

    public function forget(?Authenticatable $user = null): void
    {
        if ($user === null) {
            $this->forgetEveryone();

            return;
        }

        $this->forgetKey($user->getAuthIdentifier());
    }

    /**
     * Forget one user by key, for writes that know only `user_id`.
     */
    public function forgetKey(mixed $key): void
    {
        $user = $this->userKeyFor($key);

        unset($this->grants[$user], $this->supers[$user]);

        $this->cache->forgetUser($user);
    }

    /**
     * Forget everyone's grants, and the permission names when the catalogue
     * changed.
     */
    public function forgetEveryone(bool $names = true): void
    {
        $this->grants = [];
        $this->supers = [];

        if ($names) {
            $this->known = null;
        }

        $this->cache->forget($names);
    }

    /**
     * Every permission name Guardian has a row for, sorted.
     *
     * @return list<string>
     */
    public function known(): array
    {
        return $this->known ??= $this->cache->names(
            fn (): array => array_values(PermissionModel::query()->orderBy('name')->pluck('name')->map(strval(...))->all()),
        );
    }

    /**
     * The permissions the user's assignments grant globally or in one of the
     * scopes in the chain.
     *
     * @param  list<mixed>  $chain
     * @return list<string>
     */
    private function held(Authenticatable $user, array $chain): array
    {
        $scopes = ['' => true];

        foreach ($chain as $link) {
            if ($link instanceof Model && $link->getKey() !== null) {
                $scopes[$link->getMorphClass().'#'.$this->stringKey($link->getKey())] = true;
            }
        }

        $held = [];

        foreach ($this->grantsOf($user) as $grant) {
            $where = $grant['scope_type'] === null ? '' : $grant['scope_type'].'#'.$grant['scope_id'];

            if ($grant['permission'] !== null && isset($scopes[$where])) {
                $held[] = $grant['permission'];
            }
        }

        return $held;
    }

    /**
     * @return list<mixed>
     */
    private function chain(mixed $scope): array
    {
        if ($scope === null) {
            return [];
        }

        return $scope instanceof Scope ? ScopeChain::of($scope) : [$scope];
    }

    private function hasSuperRole(Authenticatable $user): bool
    {
        foreach ($this->grantsOf($user) as $grant) {
            if ($grant['super'] && $grant['scope_type'] === null) {
                return true;
            }
        }

        return false;
    }

    private function isConfiguredSuperAdmin(Authenticatable $user): bool
    {
        if (! $user instanceof Model) {
            return false;
        }

        $emails = array_map(
            fn (mixed $email): string => strtolower(is_string($email) ? $email : ''),
            (array) $this->config->get('guardian.super_admins', []),
        );

        $email = $this->users->email($user);

        return $email !== null
            && in_array(strtolower($email), $emails, true)
            && $this->users->emailVerified($user);
    }

    /**
     * @return list<array{scope_type: string|null, scope_id: string|null, super: bool, permission: string|null}>
     */
    private function grantsOf(Authenticatable $user): array
    {
        $key = $this->userKey($user);

        return $this->grants[$key] ??= $this->cache->grants($key, fn (): array => $this->loadGrants($user->getAuthIdentifier()));
    }

    /**
     * @return list<array{scope_type: string|null, scope_id: string|null, super: bool, permission: string|null}>
     */
    private function loadGrants(mixed $userId): array
    {
        if (! is_int($userId) && ! is_string($userId)) {
            return [];
        }

        $assignments = Tables::name('role_assignments');
        $roles = Tables::name('roles');
        $pivot = Tables::name('permission_role');
        $permissions = Tables::name('permissions');

        return array_values(RoleAssignmentModel::query()
            ->where($assignments.'.user_id', $userId)
            ->join($roles, $roles.'.id', '=', $assignments.'.role_id')
            ->leftJoin($pivot, $pivot.'.role_id', '=', $assignments.'.role_id')
            ->leftJoin($permissions, $permissions.'.id', '=', $pivot.'.permission_id')
            ->toBase()
            ->get([$assignments.'.scope_type', $assignments.'.scope_id', $roles.'.super', $permissions.'.name'])
            ->map(fn (object $row): array => [
                'scope_type' => isset($row->scope_type) && is_string($row->scope_type) ? $row->scope_type : null,
                'scope_id' => isset($row->scope_id) ? $this->stringKey($row->scope_id) : null,
                'super' => (bool) ($row->super ?? false),
                'permission' => isset($row->name) && is_string($row->name) ? $row->name : null,
            ])
            ->all());
    }

    /**
     * A scope type given as a model class or a morph alias, as stored.
     */
    private function morphType(string $type): string
    {
        return class_exists($type) && is_subclass_of($type, Model::class) ? (new $type)->getMorphClass() : $type;
    }

    private function userKey(Authenticatable $user): string
    {
        return $this->userKeyFor($user->getAuthIdentifier());
    }

    private function userKeyFor(mixed $key): string
    {
        return 'user:'.$this->stringKey($key);
    }

    private function scopeKey(mixed $scope): string
    {
        if ($scope === null) {
            return 'global';
        }

        if ($scope instanceof Model && $scope->getKey() !== null) {
            return $scope->getMorphClass().'#'.$this->stringKey($scope->getKey());
        }

        return is_object($scope) ? $scope::class.'#'.spl_object_id($scope) : get_debug_type($scope);
    }

    private function stringKey(mixed $key): string
    {
        return is_scalar($key) ? (string) $key : '';
    }
}
