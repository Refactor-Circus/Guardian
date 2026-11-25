<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Data\ListRolesData;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\ListRolesMcpRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List roles with their permissions: the shared ones, plus a scope\'s own when scope_type and scope_id name it.')]
final class ListRolesTool extends Tool
{
    use DescribesScope;

    public function handle(ListRolesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->scopeSchema($schema) + [
            'scope' => $schema->string()->description('Only roles assignable as this says: global, any, or a scope type.'),
        ] + ListRolesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleResource::collectionSchema($schema, ListRolesData::pagination());
    }
}
