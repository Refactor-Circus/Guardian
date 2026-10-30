<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Requests\SsoOrganizationMcpRequest;
use RefactorCircus\Guardian\Domains\Sso\Actions\ListSsoConnectionsAction;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource;

final class ListSsoConnectionsMcpRequest extends SsoOrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.view';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ListSsoConnectionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $connections = app(ListSsoConnectionsAction::class)->execute(ListSsoConnectionsData::fromValidated($validated));

        return SsoConnectionResource::collection($connections)->toStructuredResponse();
    }
}
