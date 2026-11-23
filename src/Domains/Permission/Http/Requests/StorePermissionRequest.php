<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Guardian\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class StorePermissionRequest extends Request
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    public function rules(): array
    {
        return CreatePermissionData::rules();
    }

    public function persist(): JsonResponse
    {
        $permission = app(CreatePermissionAction::class)->execute(CreatePermissionData::fromValidated($this->validated()));

        return (new PermissionResource($permission))->response()->setStatusCode(201);
    }
}
