<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Sso\Mcp\Requests\SsoOrganizationMcpRequest;
use RefactorCircus\Guardian\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource;

final class CreateSsoConnectionMcpRequest extends SsoOrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + CreateSsoConnectionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $connection = app(CreateSsoConnectionAction::class)->execute($this->organization(), CreateSsoConnectionData::fromValidated($validated));

        return (new SsoConnectionResource($connection))->toStructuredResponse();
    }
}
