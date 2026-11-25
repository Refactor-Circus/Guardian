<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Authorization\Contracts\Scope;
use RefactorCircus\Keystone\Authorization\ScopeChain;

/**
 * Guardian's default `TwoFactorRequirement` (criterion 34, D-013): required
 * when `guardian.two_factor.required` is on or a matching policy requires
 * it. A policy matches everyone when it has no scope and no role; the
 * holders of its role (anywhere, or within its scope when it has one); or
 * the users assigned in its scope or a scope beneath it. The allowed methods
 * are the intersection of the matching policies' lists; an empty one falls
 * back to `totp` and `passkey` and records `two_factor.policy_conflict` once a
 * day. The deadline is the earliest requiring policy's creation plus its
 * grace days (none, so due at once, under the config switch).
 */
final class PolicyTwoFactorRequirement implements TwoFactorRequirement
{
    /**
     * The methods an empty intersection falls back to.
     *
     * @var list<string>
     */
    public const array FALLBACK_METHODS = ['totp', 'passkey'];

    public function __construct(
        private readonly Repository $config,
        private readonly Scopes $scopes,
        private readonly Cache $cache,
        private readonly SecurityLog $log,
    ) {}

    public function for(Authenticatable $user): Requirement
    {
        $configured = $this->config->get('guardian.two_factor.required') === true;
        $matching = $this->matching($user);

        if (! $configured && $matching === []) {
            return new Requirement;
        }

        $requiring = array_values(array_filter($matching, fn (TwoFactorPolicyModel $policy): bool => $policy->required));

        return new Requirement(
            required: $configured || $requiring !== [],
            methods: $this->methods($user, $matching),
            deadline: $configured ? null : $this->deadline($requiring),
        );
    }

    /**
     * The policies that apply to the user.
     *
     * @return list<TwoFactorPolicyModel>
     */
    private function matching(Authenticatable $user): array
    {
        $policies = TwoFactorPolicyModel::query()->oldest()->oldest('id')->get();

        if ($policies->isEmpty()) {
            return [];
        }

        $assignments = $this->assignments($user);

        return array_values($policies->filter(fn (TwoFactorPolicyModel $policy): bool => $this->matches($policy, $assignments))->all());
    }

    /**
     * @param  list<array{role: string, chain: list<string>}>  $assignments
     */
    private function matches(TwoFactorPolicyModel $policy, array $assignments): bool
    {
        $scope = $policy->scope_type === null || $policy->scope_id === null ? null : $policy->scope_type.'|'.$policy->scope_id;

        if ($policy->role === null && $scope === null) {
            return true;
        }

        foreach ($assignments as $assignment) {
            $roleMatches = $policy->role === null || $assignment['role'] === $policy->role;
            $scopeMatches = $scope === null || in_array($scope, $assignment['chain'], true);

            if ($roleMatches && $scopeMatches) {
                return true;
            }
        }

        return false;
    }

    /**
     * The user's role assignments: the role's slug, and the assignment's
     * scope with the scopes above it (`type|id`; none for a global one).
     *
     * @return list<array{role: string, chain: list<string>}>
     */
    private function assignments(Authenticatable $user): array
    {
        $assignments = [];

        foreach (RoleAssignmentModel::query()->with('role')->where('user_id', UserKeys::of($user))->get() as $assignment) {
            $role = $assignment->role;

            if ($role === null) {
                continue;
            }

            $assignments[] = ['role' => $role->slug, 'chain' => $this->chain($assignment->scope_type, $assignment->scope_id)];
        }

        return $assignments;
    }

    /**
     * A scope and the scopes above it, as `type|id`.
     *
     * @return list<string>
     */
    private function chain(?string $type, ?string $id): array
    {
        if ($type === null || $id === null) {
            return [];
        }

        $scope = $this->scopes->find($type, $id);

        if (! $scope instanceof Scope) {
            return [$type.'|'.$id];
        }

        $chain = [];

        foreach (ScopeChain::of($scope) as $link) {
            if ($link instanceof Model) {
                $chain[] = $link->getMorphClass().'|'.Scopes::key($link);
            }
        }

        return $chain === [] ? [$type.'|'.$id] : $chain;
    }

    /**
     * The intersection of the matching policies' method lists; any method
     * when none lists them; the fallback (recorded once a day) when they
     * share none.
     *
     * @param  list<TwoFactorPolicyModel>  $matching
     * @return list<string>
     */
    private function methods(Authenticatable $user, array $matching): array
    {
        $methods = Requirement::ALL_METHODS;
        $limited = [];

        foreach ($matching as $policy) {
            if ($policy->methods !== null) {
                $methods = array_values(array_intersect($methods, $policy->methods));
                $limited[] = $policy->id;
            }
        }

        if ($methods !== []) {
            return $methods;
        }

        if ($this->cache->add('guardian:two-factor:policy-conflict:'.hash('sha256', (string) UserKeys::of($user)).':'.now()->toDateString(), true, 86400)) {
            $this->log->record(SecurityEventType::TwoFactorPolicyConflict, $user, ['policies' => $limited]);
        }

        return self::FALLBACK_METHODS;
    }

    /**
     * @param  list<TwoFactorPolicyModel>  $requiring
     */
    private function deadline(array $requiring): ?CarbonImmutable
    {
        $deadline = null;

        foreach ($requiring as $policy) {
            $due = CarbonImmutable::instance($policy->created_at)->addDays($policy->grace_days);
            $deadline = $deadline === null || $due->lessThan($deadline) ? $due : $deadline;
        }

        return $deadline;
    }
}
