<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ListTwoFactorPoliciesData;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\ListTwoFactorPoliciesMcpRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the two-factor enforcement policies, oldest first. Needs guardian.two-factor.manage.')]
final class ListTwoFactorPoliciesTool extends Tool
{
    public function handle(ListTwoFactorPoliciesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return ListTwoFactorPoliciesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TwoFactorPolicyResource::collectionSchema($schema, ListTwoFactorPoliciesData::pagination());
    }
}
