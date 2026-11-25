<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use RefactorCircus\Guardian\Domains\Role\Concerns\NamesRoles;
use RefactorCircus\Guardian\Mcp\Requests\Request;

/**
 * A tool call about roles: the scope it names (`scope_type`, `scope_id`)
 * and a role by its `role` slug.
 */
abstract class RoleMcpRequest extends Request
{
    use NamesRoles;

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function roleRules(): array
    {
        return ['role' => ['required', 'string']];
    }
}
