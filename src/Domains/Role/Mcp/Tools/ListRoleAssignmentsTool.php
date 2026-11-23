<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\ListRoleAssignmentsMcpRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleAssignmentResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List who holds which role where, oldest first. Filter by user id, role slug, scope_type and scope_id.')]
final class ListRoleAssignmentsTool extends Tool
{
    use DescribesScope;

    public function handle(ListRoleAssignmentsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('Only this user id.'),
            'role' => $schema->string()->description('Only this role slug.'),
        ] + $this->scopeSchema($schema, 'Only assignments in this scope; scope_type alone keeps every scope of that type.') + ListRoleAssignmentsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleAssignmentResource::collectionSchema($schema, ListRoleAssignmentsData::pagination());
    }
}
