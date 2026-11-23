<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\AssignRoleMcpRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleAssignmentResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Give a user a role, globally or in a scope (scope_type and scope_id). You must hold every permission of the role there; only super-admins assign super roles.')]
final class AssignRoleTool extends Tool
{
    use DescribesScope;

    public function handle(AssignRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('The user id.')->required(),
            'role' => $schema->string()->description('The role slug.')->required(),
        ] + $this->scopeSchema($schema, 'Where the user holds the role; omit both for global.');
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleAssignmentResource::structuredSchema($schema);
    }
}
