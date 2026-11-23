<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Permission\Mcp\Requests\DeletePermissionMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Delete an app permission; every role stops granting it. Built-in permissions cannot be deleted.')]
final class DeletePermissionTool extends Tool
{
    public function handle(DeletePermissionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'permission' => $schema->string()->description('The permission name.')->required(),
        ];
    }
}
