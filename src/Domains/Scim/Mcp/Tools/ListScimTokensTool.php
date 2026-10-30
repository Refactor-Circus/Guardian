<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Scim\Data\ListScimTokensData;
use RefactorCircus\Guardian\Domains\Scim\Mcp\Requests\ListScimTokensMcpRequest;
use RefactorCircus\Guardian\Domains\Scim\Resources\ScimTokenResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesOrganization;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List an organization\'s SCIM provisioning tokens (never the token values) with when each was last used.')]
final class ListScimTokensTool extends Tool
{
    use DescribesOrganization;

    public function handle(ListScimTokensMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
        ] + ListScimTokensData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ScimTokenResource::collectionSchema($schema);
    }
}
