<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Requests\CreateIpRuleMcpRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Guardian\Domains\LoginProtection\Resources\IpRuleResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Allow or deny an IPv4 or IPv6 address or CIDR range, optionally until a time. Deny always wins; in allowlist mode only allowed addresses get in. Needs guardian.security.manage.')]
final class CreateIpRuleTool extends Tool
{
    public function handle(CreateIpRuleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cidr' => $schema->string()->description('An address or CIDR range, such as 203.0.113.0/24 or 2001:db8::/32.')->required(),
            'action' => $schema->string()->enum([IpRuleModel::ALLOW, IpRuleModel::DENY])->required(),
            'description' => $schema->string()->description('Why the rule exists.'),
            'expires_at' => $schema->string()->description('When the rule stops applying (ISO 8601); omit for never.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return IpRuleResource::structuredSchema($schema);
    }
}
