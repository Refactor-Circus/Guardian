<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Permission\Actions\ListPermissionsAction;
use RefactorCircus\Guardian\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ListPermissionsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    protected function rules(): array
    {
        return ListPermissionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return PermissionResource::collection(app(ListPermissionsAction::class)->execute(ListPermissionsData::fromValidated($validated)))->toStructuredResponse();
    }
}
