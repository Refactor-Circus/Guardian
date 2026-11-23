<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\CreateRoleMcpRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Create a role granting the given permissions. scope is where it may be assigned (global, any, or a scope type); scope_type and scope_id make it that scope\'s own. You can only grant permissions you hold, and only super-admins create super roles.')]
final class CreateRoleTool extends Tool
{
    use DescribesScope;

    public function handle(CreateRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Display name.')->required(),
            'slug' => $schema->string()->description('URL identifier. Omit to generate one from the name.'),
            'scope' => $schema->string()->description('Where it may be assigned: global, any (the default), or a scope type.'),
            'description' => $schema->string(),
            'super' => $schema->boolean()->description('A super role may do anything. Super-admins only.'),
            'permissions' => $schema->array()->items($schema->string())->description('Permission names.'),
        ] + $this->scopeSchema($schema, 'The scope the role belongs to; omit both for a shared role.');
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleResource::structuredSchema($schema);
    }
}
