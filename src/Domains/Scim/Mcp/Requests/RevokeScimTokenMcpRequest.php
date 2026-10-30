<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Scim\Actions\RevokeScimTokenAction;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Resources\ScimTokenResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class RevokeScimTokenMcpRequest extends Request
{
    private ?ScimTokenModel $resolved = null;

    protected function ability(): string
    {
        return 'guardian.scim.manage';
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->token()->organization;
    }

    protected function rules(): array
    {
        return ['token' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new ScimTokenResource(app(RevokeScimTokenAction::class)->execute($this->token())))->toStructuredResponse();
    }

    private function token(): ScimTokenModel
    {
        return $this->resolved ??= ScimTokenModel::query()->whereKey($this->get('token'))->firstOrFail();
    }
}
