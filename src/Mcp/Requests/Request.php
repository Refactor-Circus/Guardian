<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Mcp\Requests\Request as BaseRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * Base MCP request for Guardian, on refactor-circus/keystone's shared request.
 *
 * Mirrors the HTTP request's `persist()` pattern so tools stay one line and
 * both surfaces resolve the same Actions; the shared base marks every call as
 * coming over MCP. Authorization is the HTTP request's: the request's
 * `ability()` in its `scope()`, for its `self()`, checked by Roster's
 * Authorizer against the signed-in user.
 */
abstract class Request extends BaseRequest
{
    protected function authorize(): bool
    {
        if (app(Authorizer::class)->check($this->actor(), $this->ability(), $this->scope(), $this->self())) {
            return true;
        }

        return ($this->organizations() ?? []) !== [];
    }

    /**
     * The permission the request's Action needs.
     */
    abstract protected function ability(): string;

    /**
     * The organization or team the permission is checked in; null for global.
     */
    protected function scope(): OrganizationModel|TeamModel|null
    {
        return null;
    }

    /**
     * The user the request is about, when everyone may do it to themselves.
     */
    protected function self(): ?Model
    {
        return null;
    }

    /**
     * Lists return true: asked without an organization of their own, a user
     * holding the permission only in some organizations sees what falls
     * within those, rather than being refused.
     */
    protected function acrossOrganizations(): bool
    {
        return false;
    }

    /**
     * The organizations a list is limited to, or null for no limit: the user
     * holds the permission globally, or the request names its own scope.
     *
     * @return array<int, int|string>|null
     */
    protected function organizations(): ?array
    {
        if (! $this->acrossOrganizations() || $this->scope() !== null) {
            return null;
        }

        $authorizer = app(Authorizer::class);

        if ($authorizer->check($this->actor(), $this->ability(), null, $this->self())) {
            return null;
        }

        return $authorizer->organizationsWith($this->actor(), $this->ability());
    }

    /**
     * The signed-in user, whom Roster's Authorizer checks and Actions that
     * guard against acting on yourself compare with.
     */
    protected function actor(): ?Model
    {
        $user = $this->user();

        return $user instanceof Model ? $user : null;
    }
}
