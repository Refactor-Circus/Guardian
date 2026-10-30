<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Scim\Actions\ListScimTokensAction;
use RefactorCircus\Guardian\Domains\Scim\Data\ListScimTokensData;
use RefactorCircus\Guardian\Domains\Scim\Resources\ScimTokenResource;

final class IndexScimTokensRequest extends ScimOrganizationRequest
{
    protected function ability(): string
    {
        return 'guardian.scim.manage';
    }

    public function rules(): array
    {
        return ListScimTokensData::rules();
    }

    public function persist(): JsonResponse
    {
        return ScimTokenResource::collection(app(ListScimTokensAction::class)->execute($this->organization(), ListScimTokensData::fromValidated($this->validated())))->response();
    }
}
