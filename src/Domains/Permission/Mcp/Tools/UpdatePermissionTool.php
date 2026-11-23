<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Permission\Mcp\Requests\UpdatePermissionMcpRequest;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Rename a permission or change its description. Built-in permissions keep their names.')]
final class UpdatePermissionTool extends Tool
{
    public function handle(UpdatePermissionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'permission' => $schema->string()->description('The permission name.')->required(),
            'name' => $schema->string()->description('A new dotted name.'),
            'description' => $schema->string()->description('What it allows; null clears it.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return PermissionResource::structuredSchema($schema);
    }
}
