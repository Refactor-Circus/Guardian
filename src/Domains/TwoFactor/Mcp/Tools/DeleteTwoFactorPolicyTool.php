<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\DeleteTwoFactorPolicyMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Delete a two-factor enforcement policy. Needs guardian.two-factor.manage.')]
final class DeleteTwoFactorPolicyTool extends Tool
{
    public function handle(DeleteTwoFactorPolicyMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'policy' => $schema->string()->description('The policy id.')->required(),
        ];
    }
}
