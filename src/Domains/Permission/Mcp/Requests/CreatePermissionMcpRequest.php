<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Guardian\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class CreatePermissionMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    protected function rules(): array
    {
        return CreatePermissionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new PermissionResource(app(CreatePermissionAction::class)->execute(CreatePermissionData::fromValidated($validated))))->toStructuredResponse();
    }
}
