<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\ListIpRulesData;
use RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Requests\ListIpRulesMcpRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Resources\IpRuleResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the IP rules (allowed and denied addresses and ranges), oldest first. Needs guardian.security.manage.')]
final class ListIpRulesTool extends Tool
{
    public function handle(ListIpRulesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return ListIpRulesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return IpRuleResource::collectionSchema($schema, ListIpRulesData::pagination());
    }
}
