<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Session\Data\ListSessionsData;
use RefactorCircus\Guardian\Domains\Session\Mcp\Requests\ListSessionsMcpRequest;
use RefactorCircus\Guardian\Domains\Session\Resources\SessionResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List your own active sessions, most recently active first. Over MCP none is the current one.')]
final class ListSessionsTool extends Tool
{
    public function handle(ListSessionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return ListSessionsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SessionResource::collectionSchema($schema, ListSessionsData::pagination());
    }
}
