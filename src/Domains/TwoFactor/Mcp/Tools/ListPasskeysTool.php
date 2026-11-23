<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\ListPasskeysMcpRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\PasskeyResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List your own passkeys. Answers an error when passkeys are not available here (web-auth/webauthn-lib not installed).')]
final class ListPasskeysTool extends Tool
{
    public function handle(ListPasskeysMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object(PasskeyResource::schema($schema)))->required(),
        ];
    }
}
