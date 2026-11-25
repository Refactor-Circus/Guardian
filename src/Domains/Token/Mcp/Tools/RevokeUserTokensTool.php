<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Token\Mcp\Requests\RevokeUserTokensMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Revoke every API token of another user. Needs guardian.tokens.manage.')]
final class RevokeUserTokensTool extends Tool
{
    public function handle(RevokeUserTokensMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('The user\'s id.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'revoked' => $schema->integer()->description('The tokens revoked.')->required(),
        ];
    }
}
