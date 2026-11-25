<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\ResetTwoFactorMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Reset a user\'s two-factor (a lost device): removes every method, passkey and recovery code, and records it. Needs guardian.two-factor.manage.')]
final class ResetTwoFactorTool extends Tool
{
    public function handle(ResetTwoFactorMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('The user\'s id.')->required(),
        ];
    }
}
