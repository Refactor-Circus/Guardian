<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\UpdateRoleMcpRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Rename a role or replace its permissions. You can only add permissions you hold, and only super-admins change super roles.')]
final class UpdateRoleTool extends Tool
{
    use DescribesScope;

    public function handle(UpdateRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'role' => $schema->string()->description('The role slug.')->required(),
            'name' => $schema->string(),
            'description' => $schema->string(),
            'permissions' => $schema->array()->items($schema->string())->description('Replaces the role permission names.'),
        ] + $this->scopeSchema($schema, 'The scope a custom role belongs to; omit both for a shared role.');
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleResource::structuredSchema($schema);
    }
}
