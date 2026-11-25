<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Http\Requests;

use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * A request about one permission, named by its name in the route.
 * Permissions are global: every check is.
 */
abstract class PermissionRequest extends Request
{
    private ?PermissionModel $resolved = null;

    protected function permission(): PermissionModel
    {
        $name = $this->route('permission');

        return $this->resolved ??= PermissionModel::query()->where('name', is_string($name) ? $name : '')->firstOrFail();
    }
}
