<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Keystone\Http\Requests\Request as BaseRequest;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Base HTTP request for Guardian, on refactor-circus/keystone's shared request.
 *
 * Validation rules come from the Data the request's Action takes, and
 * `persist()` calls that Action; the MCP surface does the same. A request
 * names the permission its Action needs (`ability()`), the scope it applies
 * to (`scope()`) and the user it is about when everyone may do it to
 * themselves (`self()`), and Guardian's `Authorization` checks that against
 * the signed-in user through Keystone's bound `Permissions`, as Guardian's
 * policies and the MCP requests do. Guests get a 401, signed-in users without
 * the permission a 403.
 *
 * Only lists across organizations name Roster (D-003): SSO and SCIM limit
 * them to the organizations the user may use the permission in.
 */
abstract class Request extends BaseRequest
{
    public function authorize(): bool
    {
        $authorization = app(Authorization::class);

        if ($authorization->check($this->actor(), $this->ability(), $this->scope(), $this->self())) {
            return true;
        }

        return ($this->organizations() ?? []) !== [];
    }

    /**
     * The permission the request's Action needs.
     */
    abstract protected function ability(): string;

    /**
     * The scope the permission is checked in (an organization or team for
     * SSO and SCIM); null for global.
     */
    protected function scope(): ?Model
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
     * holds the permission globally, or the request names its own scope. The
     * organizations whose own rule (their owner) grants it count too.
     *
     * @return array<int, int|string>|null
     */
    protected function organizations(): ?array
    {
        if (! $this->acrossOrganizations() || $this->scope() !== null) {
            return null;
        }

        $authorization = app(Authorization::class);
        $actor = $this->actor();

        if ($authorization->check($actor, $this->ability(), null, $this->self())) {
            return null;
        }

        $scopes = $authorization->scopesWith($actor, $this->ability(), (new OrganizationModel)->getMorphClass());

        if ($scopes === null || ! $actor instanceof Authenticatable) {
            return $scopes;
        }

        $owned = OrganizationModel::query()
            ->where('owner_id', $actor->getAuthIdentifier())
            ->get()
            ->filter(fn (OrganizationModel $organization): bool => $organization->grantsByItself($actor, $this->ability()))
            ->modelKeys();

        return array_values(array_unique([...$scopes, ...$owned], SORT_REGULAR));
    }

    /**
     * The signed-in user, whom the check asks about and Actions that guard
     * against acting on yourself compare with.
     */
    protected function actor(): ?Model
    {
        $user = $this->user();

        return $user instanceof Model ? $user : null;
    }
}
