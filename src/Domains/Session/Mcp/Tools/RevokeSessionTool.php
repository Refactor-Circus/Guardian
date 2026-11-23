<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Session\Mcp\Requests\RevokeSessionMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Revoke one of your own sessions: it is signed out on its next request.')]
final class RevokeSessionTool extends Tool
{
    public function handle(RevokeSessionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'session' => $schema->string()->description('The session id.')->required(),
        ];
    }
}
