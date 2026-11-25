<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\ShowRoleMcpRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Show a role by slug with the permissions it grants. Name a scope to see its own roles.')]
final class ShowRoleTool extends Tool
{
    use DescribesScope;

    public function handle(ShowRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'role' => $schema->string()->description('The role slug.')->required(),
        ] + $this->scopeSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleResource::structuredSchema($schema);
    }
}
