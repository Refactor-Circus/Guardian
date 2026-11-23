<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Requests\ClearLockoutMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Lift the sign-in lockout of an identifier (an email or username) after too many failed attempts. Needs guardian.security.manage.')]
final class ClearLockoutTool extends Tool
{
    public function handle(ClearLockoutMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'identifier' => $schema->string()->description('The sign-in identifier, as typed at sign-in.')->required(),
        ];
    }
}
