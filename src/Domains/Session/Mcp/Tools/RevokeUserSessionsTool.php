<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Session\Mcp\Requests\RevokeUserSessionsMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Revoke every session of another user. Needs guardian.sessions.manage.')]
final class RevokeUserSessionsTool extends Tool
{
    public function handle(RevokeUserSessionsMcpRequest $request): Response|ResponseFactory
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
            'revoked' => $schema->integer()->description('The sessions revoked.')->required(),
        ];
    }
}
