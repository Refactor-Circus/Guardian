<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Requests\UnlinkSsoIdentityMcpRequest;

#[Description('Unlink an identity-provider account from its user. Users may unlink their own.')]
final class UnlinkSsoIdentityTool extends Tool
{
    public function handle(UnlinkSsoIdentityMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'identity' => $schema->string()->description('The identity id.')->required(),
        ];
    }
}
