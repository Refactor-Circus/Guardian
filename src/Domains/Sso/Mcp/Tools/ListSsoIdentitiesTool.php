<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Requests\ListSsoIdentitiesMcpRequest;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoIdentityResource;
use RefactorCircus\Guardian\Mcp\Concerns\DescribesUser;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the identity-provider accounts linked to a user.')]
final class ListSsoIdentitiesTool extends Tool
{
    use DescribesUser;

    public function handle(ListSsoIdentitiesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
        ] + ListSsoIdentitiesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SsoIdentityResource::collectionSchema($schema);
    }
}
