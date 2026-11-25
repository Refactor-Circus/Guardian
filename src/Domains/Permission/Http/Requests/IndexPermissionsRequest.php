<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Permission\Actions\ListPermissionsAction;
use RefactorCircus\Guardian\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class IndexPermissionsRequest extends Request
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    public function rules(): array
    {
        return ListPermissionsData::rules();
    }

    public function persist(): JsonResponse
    {
        return PermissionResource::collection(app(ListPermissionsAction::class)->execute(ListPermissionsData::fromValidated($this->validated())))->response();
    }
}
