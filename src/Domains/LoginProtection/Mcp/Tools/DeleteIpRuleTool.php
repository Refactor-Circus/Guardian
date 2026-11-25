<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Requests\DeleteIpRuleMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Delete an IP rule. Needs guardian.security.manage.')]
final class DeleteIpRuleTool extends Tool
{
    public function handle(DeleteIpRuleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'rule' => $schema->string()->description('The rule id.')->required(),
        ];
    }
}
