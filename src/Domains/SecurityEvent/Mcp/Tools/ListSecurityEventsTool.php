<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\ListSecurityEventsData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Mcp\Requests\ListSecurityEventsMcpRequest;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Read the security log, newest first: sign-ins, failures, lockouts, two-factor, session, token and IP events. Filter by type, user id and time. Needs guardian.security.view.')]
final class ListSecurityEventsTool extends Tool
{
    public function handle(ListSecurityEventsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(SecurityEventType::values())->description('Only this type.'),
            'from' => $schema->string()->description('Only events at or after this time (ISO 8601).'),
            'to' => $schema->string()->description('Only events at or before this time (ISO 8601).'),
            'user' => $schema->string()->description('Only this user id.'),
        ] + ListSecurityEventsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SecurityEventResource::collectionSchema($schema, ListSecurityEventsData::pagination());
    }
}
