<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Session\Mcp\Requests\RevokeOtherSessionsMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Sign out your sessions. Over MCP there is no current session, so every one goes. With tokens: true your API tokens are revoked too, through the configured token driver.')]
final class RevokeOtherSessionsTool extends Tool
{
    public function handle(RevokeOtherSessionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tokens' => $schema->boolean()->description('Also revoke your API tokens.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'revoked' => $schema->integer()->description('The sessions revoked.')->required(),
            'tokens_revoked' => $schema->integer()->nullable()->description('The API tokens revoked; null when tokens were not asked for.')->required(),
        ];
    }
}
