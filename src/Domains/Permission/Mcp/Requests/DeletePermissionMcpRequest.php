<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\Permission\Actions\DeletePermissionAction;

final class DeletePermissionMcpRequest extends PermissionMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    protected function rules(): array
    {
        return $this->permissionRules();
    }

    protected function handle(array $validated): Response
    {
        app(DeletePermissionAction::class)->execute($this->permission());

        return Response::text('Permission deleted.');
    }
}
