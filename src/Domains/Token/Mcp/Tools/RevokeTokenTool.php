<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Token\Mcp\Requests\RevokeTokenMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Revoke one of your API tokens. Tokens cannot be issued over MCP.')]
final class RevokeTokenTool extends Tool
{
    public function handle(RevokeTokenMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'token' => $schema->string()->description('The token id.')->required(),
        ];
    }
}
