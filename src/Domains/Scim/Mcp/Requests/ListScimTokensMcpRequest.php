<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Scim\Actions\ListScimTokensAction;
use RefactorCircus\Guardian\Domains\Scim\Data\ListScimTokensData;
use RefactorCircus\Guardian\Domains\Scim\Resources\ScimTokenResource;

final class ListScimTokensMcpRequest extends ScimOrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.scim.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ListScimTokensData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $tokens = app(ListScimTokensAction::class)->execute($this->organization(), ListScimTokensData::fromValidated($validated));

        return ScimTokenResource::collection($tokens)->toStructuredResponse();
    }
}
