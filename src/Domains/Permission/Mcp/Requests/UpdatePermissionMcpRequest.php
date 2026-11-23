<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Permission\Actions\UpdatePermissionAction;
use RefactorCircus\Guardian\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;

final class UpdatePermissionMcpRequest extends PermissionMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    protected function rules(): array
    {
        return $this->permissionRules() + UpdatePermissionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new PermissionResource(app(UpdatePermissionAction::class)->execute($this->permission(), UpdatePermissionData::fromValidated($validated))))->toStructuredResponse();
    }
}
