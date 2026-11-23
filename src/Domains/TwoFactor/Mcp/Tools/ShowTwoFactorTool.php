<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\ShowTwoFactorMcpRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorStatusResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Show a user\'s two-factor status: their methods (destinations masked), passkeys, recovery codes left and what is required of them. Yours when no user is given; another user\'s needs guardian.two-factor.manage.')]
final class ShowTwoFactorTool extends Tool
{
    public function handle(ShowTwoFactorMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->string()->description('The user\'s id; yourself when left out.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TwoFactorStatusResource::structuredSchema($schema);
    }
}
