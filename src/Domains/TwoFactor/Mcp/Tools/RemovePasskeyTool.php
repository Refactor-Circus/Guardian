<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\RemovePasskeyMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Remove one of your own passkeys, with a current code from another of your factors or a recovery code. It cannot be your last factor while two-factor is required.')]
final class RemovePasskeyTool extends Tool
{
    public function handle(RemovePasskeyMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'passkey' => $schema->string()->description('The passkey\'s id.')->required(),
            'code' => $schema->string()->description('A current code from another factor, or a recovery code.'),
        ];
    }
}
