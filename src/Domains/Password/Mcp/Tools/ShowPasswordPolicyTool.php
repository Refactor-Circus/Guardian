<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Password\Mcp\Requests\ShowPasswordPolicyMcpRequest;
use RefactorCircus\Guardian\Domains\Password\Resources\PasswordPolicyResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Show the password rules: lengths, required character kinds, the breach check, how many old passwords cannot be reused and when passwords expire.')]
final class ShowPasswordPolicyTool extends Tool
{
    public function handle(ShowPasswordPolicyMcpRequest $request): Response|ResponseFactory
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
            'data' => $schema->object(PasswordPolicyResource::schema($schema))->required(),
        ];
    }
}
