<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Requests\ListSsoConnectionsMcpRequest;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesOrganization;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List an organization\'s single sign-on connections. Secrets are never returned.')]
final class ListSsoConnectionsTool extends Tool
{
    use DescribesOrganization;

    public function handle(ListSsoConnectionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
        ] + ListSsoConnectionsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SsoConnectionResource::collectionSchema($schema);
    }
}
