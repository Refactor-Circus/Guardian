<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Requests\DeleteSsoConnectionMcpRequest;

#[Description('Delete an SSO connection and its linked identities. Accounts stay.')]
final class DeleteSsoConnectionTool extends Tool
{
    public function handle(DeleteSsoConnectionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'connection' => $schema->string()->description('The connection slug.')->required(),
        ];
    }
}
