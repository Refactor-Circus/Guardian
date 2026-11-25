<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Role\Mcp\Requests\RevokeRoleMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Take a role away: revoke one assignment by its id. You must hold every permission of the role there; only super-admins revoke super roles.')]
final class RevokeRoleTool extends Tool
{
    public function handle(RevokeRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'assignment' => $schema->string()->description('The assignment id, as list-role-assignments-tool returns it.')->required(),
        ];
    }
}
