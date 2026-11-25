<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Guardian\Domains\Permission\Mcp\Requests\ListPermissionsMcpRequest;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the permissions roles can grant, by name. search keeps the names containing it.')]
final class ListPermissionsTool extends Tool
{
    public function handle(ListPermissionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Only names containing this.'),
        ] + ListPermissionsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return PermissionResource::collectionSchema($schema, ListPermissionsData::pagination());
    }
}
