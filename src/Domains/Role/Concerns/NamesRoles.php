<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Support\RoleFinder;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Guardian\Support\Users;

/**
 * What an HTTP request or MCP call about roles names: a scope
 * (`scope_type` and `scope_id`), a role by slug seen from that scope, a user
 * by key and an assignment by id.
 */
trait NamesRoles
{
    private ?Model $namedScope = null;

    private bool $namedScopeResolved = false;

    private ?RoleModel $namedRole = null;

    private ?Model $namedUser = null;

    private ?RoleAssignmentModel $namedAssignment = null;

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

    /**
     * The scope a list names in full (type and id), for its check; a type
     * alone filters the list, checked globally.
     */
    protected function filterScope(): ?Model
    {
        $id = $this->argument('scope_id');

        return $id === null || $id === '' ? null : $this->namedScope();
    }

    /**
     * The user the caller names by key.
     *
     * @throws ModelNotFoundException<Model>
     */
    protected function namedUser(): Model
    {
        $key = $this->argument('user');
        $user = is_int($key) || is_string($key) ? app(Users::class)->find($key) : null;

        return $this->namedUser ??= $user ?? throw (new ModelNotFoundException)->setModel(app(Users::class)->model());
    }

    /**
     * The assignment the caller names by id.
     */
    protected function namedAssignment(): RoleAssignmentModel
    {
        $id = $this->argument('assignment');

        return $this->namedAssignment ??= RoleAssignmentModel::query()->whereKey(is_scalar($id) ? (string) $id : '')->firstOrFail();
    }

    /**
     * Where an assignment is held: its scope, or null (global).
     */
    protected function assignmentScope(): ?Model
    {
        $assignment = $this->namedAssignment();

        return app(Scopes::class)->find($assignment->scope_type, $assignment->scope_id);
    }
}
