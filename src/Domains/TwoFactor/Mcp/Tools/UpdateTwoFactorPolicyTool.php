<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests\UpdateTwoFactorPolicyMcpRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Change whether a two-factor policy requires it, its methods or its grace days. Needs guardian.two-factor.manage.')]
final class UpdateTwoFactorPolicyTool extends Tool
{
    public function handle(UpdateTwoFactorPolicyMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'policy' => $schema->string()->description('The policy id.')->required(),
            'required' => $schema->boolean()->description('Whether it requires two-factor.'),
            'methods' => $schema->array()->items($schema->string()->enum(Requirement::ALL_METHODS))->description('The methods that count; null for any.'),
            'grace_days' => $schema->integer()->description('Days users have to enrol.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TwoFactorPolicyResource::structuredSchema($schema);
    }
}
