<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Token\Mcp\Requests\ListUserTokensMcpRequest;
use RefactorCircus\Guardian\Domains\Token\Resources\TokenResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List another user\'s API tokens (never their values). Needs guardian.tokens.manage.')]
final class ListUserTokensTool extends Tool
{
    public function handle(ListUserTokensMcpRequest $request): Response|ResponseFactory
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
        return TokenResource::listSchema($schema);
    }
}
