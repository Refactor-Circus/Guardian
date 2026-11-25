<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\ListSecurityEventsData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Requests\ListMySecurityEventsMcpRequest;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Read your own security events, newest first. Filter by type and time.')]
final class ListMySecurityEventsTool extends Tool
{
    public function handle(ListMySecurityEventsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(SecurityEventType::values())->description('Only this type.'),
            'from' => $schema->string()->description('Only events at or after this time (ISO 8601).'),
            'to' => $schema->string()->description('Only events at or before this time (ISO 8601).'),
        ] + ListSecurityEventsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SecurityEventResource::collectionSchema($schema, ListSecurityEventsData::pagination());
    }
}
