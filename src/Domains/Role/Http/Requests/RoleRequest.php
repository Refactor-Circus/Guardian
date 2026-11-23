<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use RefactorCircus\Guardian\Domains\Role\Concerns\NamesRoles;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * A request about roles: the scope it names (`scope_type`, `scope_id`) and
 * the role in the route, by slug.
 */
abstract class RoleRequest extends Request
{
    use NamesRoles;

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
