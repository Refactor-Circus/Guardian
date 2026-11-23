<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\DeleteRoleMcpRequest;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Delete a role and every assignment of it. Built-in roles cannot be deleted.')]
final class DeleteRoleTool extends Tool
{
    use DescribesScope;

    public function handle(DeleteRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'role' => $schema->string()->description('The role slug.')->required(),
        ] + $this->scopeSchema($schema, 'The scope a custom role belongs to; omit both for a shared role.');
    }
}
