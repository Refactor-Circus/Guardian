<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Guardian\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Guardian\Domains\Scim\Resources\ScimTokenResource;

final class CreateScimTokenMcpRequest extends ScimOrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.scim.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + CreateScimTokenData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $issued = app(CreateScimTokenAction::class)->execute($this->organization(), CreateScimTokenData::fromValidated($validated), $this->actor());

        return (new ScimTokenResource($issued->token))->additional(['token' => $issued->plain])->toStructuredResponse();
    }
}
