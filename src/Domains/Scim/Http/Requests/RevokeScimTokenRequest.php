<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Scim\Actions\RevokeScimTokenAction;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Resources\ScimTokenResource;
use RefactorCircus\Guardian\Http\Requests\Request;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class RevokeScimTokenRequest extends Request
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

    public function persist(): JsonResponse
    {
        return (new ScimTokenResource(app(RevokeScimTokenAction::class)->execute($this->token())))->response();
    }

    private function token(): ScimTokenModel
    {
        return $this->resolved ??= ScimTokenModel::query()->whereKey($this->route('token'))->firstOrFail();
    }
}
