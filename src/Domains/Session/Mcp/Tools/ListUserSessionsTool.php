<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Session\Data\ListSessionsData;
use RefactorCircus\Guardian\Domains\Session\Mcp\Requests\ListUserSessionsMcpRequest;
use RefactorCircus\Guardian\Domains\Session\Resources\SessionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List another user\'s active sessions. Needs guardian.sessions.manage.')]
final class ListUserSessionsTool extends Tool
{
    public function handle(ListUserSessionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return ['user' => $schema->string()->description('The user\'s id.')->required()] + ListSessionsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SessionResource::collectionSchema($schema, ListSessionsData::pagination());
    }
}
