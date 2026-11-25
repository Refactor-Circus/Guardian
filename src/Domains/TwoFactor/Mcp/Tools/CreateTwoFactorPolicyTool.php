<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\CreateTwoFactorPolicyMcpRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesScope;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Make a two-factor enforcement policy: for everyone (no scope, no role), a role\'s holders, a scope\'s members (and below), or a role within a scope. Needs guardian.two-factor.manage.')]
final class CreateTwoFactorPolicyTool extends Tool
{
    use DescribesScope;

    public function handle(CreateTwoFactorPolicyMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->scopeSchema($schema, 'The scope whose members it covers; omit both for any.') + [
            'role' => $schema->string()->description('The role slug whose holders it covers.'),
            'required' => $schema->boolean()->description('Whether it requires two-factor (default true).'),
            'methods' => $schema->array()->items($schema->string()->enum(Requirement::ALL_METHODS))->description('The methods that count; omit for any.'),
            'grace_days' => $schema->integer()->description('Days users have to enrol (default 0).'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TwoFactorPolicyResource::structuredSchema($schema);
    }
}
