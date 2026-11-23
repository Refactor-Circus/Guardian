<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Permission\Actions\UpdatePermissionAction;
use RefactorCircus\Guardian\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;

final class UpdatePermissionRequest extends PermissionRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    public function rules(): array
    {
        return UpdatePermissionData::rules();
    }

    public function persist(): JsonResponse
    {
        $permission = app(UpdatePermissionAction::class)->execute($this->permission(), UpdatePermissionData::fromValidated($this->validated()));

        return (new PermissionResource($permission))->response();
    }
}
