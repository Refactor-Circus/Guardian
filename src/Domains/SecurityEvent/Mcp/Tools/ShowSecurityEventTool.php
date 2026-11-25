<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Requests\ShowSecurityEventMcpRequest;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Show one security event by id.')]
final class ShowSecurityEventTool extends Tool
{
    public function handle(ShowSecurityEventMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'event' => $schema->string()->description('The event id.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SecurityEventResource::structuredSchema($schema);
    }
}
