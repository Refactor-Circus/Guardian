<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Support;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;

/**
 * How Guardian's JSON API, MCP tools and policies decide whether the caller
 * may use one of Guardian's abilities.
 *
 * Every answer comes from Keystone's bound `Permissions` (a role store such
 * as Guardian's own, or the Gate fallback), in the scope the caller names:
 * the same check Keystone's `Authorizer::allows()` makes. An ability whose
 * prefix is registered as a resource (`guardian.sso` => the connection model)
 * is answered by that model's policy from `guardian.policies`, as the
 * camel-cased verb, so a host that swaps the policy changes the API and MCP
 * too; the bundled policies ask `permits()`.
 *
 * `guardian.authorization.enabled` off leaves the route middleware as the
 * only check (D-026).
 */
final class Authorization
{
    /**
     * The model whose policy answers each ability prefix.
     *
     * @var array<string, class-string<Model>>
     */
    private array $resources = [];

    public function __construct(
        private readonly Repository $config,
        private readonly Gate $gate,
    ) {}

    public function enabled(): bool
    {
        return $this->config->get('guardian.authorization.enabled', true) !== false;
    }

    /**
     * Let a model's policy answer the abilities under a prefix: the policy's
     * `view()` answers `{prefix}.view`. Registering a prefix again replaces
     * its model.
     *
     * @param  class-string<Model>  $model
     */
    public function resource(string $prefix, string $model): self
    {
        $this->resources[$prefix] = $model;

        return $this;
    }

    /**
     * Whether the actor may use the ability in the scope, as the policy of
     * the ability's resource answers, or `permits()` when it has none.
     * `$self` names the user a call is about, for what everyone may do to
     * themselves.
     */
    public function check(?Model $actor, string $ability, ?Model $scope = null, ?Model $self = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        $model = $this->resources[Str::beforeLast($ability, '.')] ?? null;

        if ($model === null || $this->gate->getPolicyFor($model) === null) {
            return $this->permits($actor, $ability, $scope, $self);
        }

        return $this->gate->forUser($actor)->allows(Str::camel(Str::afterLast($ability, '.')), [$model, $scope, $self]);
    }

    /**
     * Whether the actor may use the ability in the scope, as Keystone's
     * bound `Permissions` answers. Everyone may act on themselves where
     * `$self` says so; a guest may do nothing.
     */
    public function permits(?Model $actor, string $ability, ?Model $scope = null, ?Model $self = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (! $actor instanceof Authenticatable) {
            return false;
        }

        if ($self !== null && $actor->is($self)) {
            return true;
        }

        return $this->permissions()->allows($actor, $ability, $scope);
    }

    /**
     * The keys of the scopes of a type the actor may use the ability in, for
     * lists limited to them; null when nothing limits them (they hold it
     * globally, or authorization is off).
     *
     * @return list<int|string>|null
     */
    public function scopesWith(?Model $actor, string $ability, string $scopeType): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        if (! $actor instanceof Authenticatable) {
            return [];
        }

        return $this->permissions()->scopesWith($actor, $ability, $scopeType);
    }

    /**
     * Looked up on every call, so a binding replaced later (a test fake, a
     * decorator) still answers.
     */
    private function permissions(): Permissions
    {
        return app(Permissions::class);
    }
}
