<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Requests;

use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Mcp\Requests\Request;

/**
 * A tool call about one permission, named by its `permission` name.
 */
abstract class PermissionMcpRequest extends Request
{
    private ?PermissionModel $resolved = null;

    protected function permission(): PermissionModel
    {
        $name = $this->get('permission');

        return $this->resolved ??= PermissionModel::query()->where('name', is_string($name) ? $name : '')->firstOrFail();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function permissionRules(): array
    {
        return ['permission' => ['required', 'string']];
    }
}
