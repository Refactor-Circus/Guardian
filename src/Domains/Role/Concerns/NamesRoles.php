<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Concerns;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Support\RoleFinder;
use RefactorCircus\Guardian\Support\Scopes;

/**
 * What an HTTP request or MCP call about roles names: a scope
 * (`scope_type` and `scope_id`) and a role by slug, seen from that scope.
 */
trait NamesRoles
{
    private ?Model $namedScope = null;

    private bool $namedScopeResolved = false;

    private ?RoleModel $namedRole = null;

    /**
     * An argument the caller sent: a route parameter, the query or the body
     * over HTTP; an argument over MCP.
     */
    abstract protected function argument(string $key): mixed;

    /**
     * The scope the caller names; null for none.
     */
    protected function namedScope(): ?Model
    {
        if (! $this->namedScopeResolved) {
            $this->namedScope = app(Scopes::class)->resolve($this->argument('scope_type'), $this->argument('scope_id'));
            $this->namedScopeResolved = true;
        }

        return $this->namedScope;
    }

    /**
     * The role the caller names by slug, seen from the named scope.
     */
    protected function role(): RoleModel
    {
        $slug = $this->argument('role');

        return $this->namedRole ??= app(RoleFinder::class)->find(is_scalar($slug) ? (string) $slug : '', $this->namedScope());
    }

    /**
     * Where the role lives: its owner, or null (global) for a shared role.
     */
    protected function roleOwner(): ?Model
    {
        return app(RoleFinder::class)->owner($this->role());
    }
}
