<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\RemoveTwoFactorMethodMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Remove one of your own two-factor methods. A confirmed one needs a current code from another of your factors, or a recovery code, and cannot be your last while two-factor is required.')]
final class RemoveTwoFactorMethodTool extends Tool
{
    public function handle(RemoveTwoFactorMethodMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'method' => $schema->string()->description('The method\'s id.')->required(),
            'code' => $schema->string()->description('A current code from another factor, or a recovery code.'),
        ];
    }
}
