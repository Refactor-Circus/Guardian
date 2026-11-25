<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Permission\Mcp\Requests\CreatePermissionMcpRequest;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Declare an app permission (a dotted name such as invoices.edit) for roles to grant.')]
final class CreatePermissionTool extends Tool
{
    public function handle(CreatePermissionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Dotted lowercase name, e.g. invoices.edit.')->required(),
            'description' => $schema->string()->description('What it allows.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return PermissionResource::structuredSchema($schema);
    }
}
