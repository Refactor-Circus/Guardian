<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\ListUserAbilitiesMcpRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\UserAbilitiesResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the abilities a user holds, globally or in a scope (scope_type and scope_id). Everyone may list their own.')]
final class ListUserAbilitiesTool extends Tool
{
    use DescribesScope;

    public function handle(ListUserAbilitiesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('The user id.')->required(),
        ] + $this->scopeSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return UserAbilitiesResource::structuredSchema($schema);
    }
}
