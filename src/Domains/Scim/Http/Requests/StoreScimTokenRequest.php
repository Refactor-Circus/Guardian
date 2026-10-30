<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Guardian\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Guardian\Domains\Scim\Resources\ScimTokenResource;

final class StoreScimTokenRequest extends ScimOrganizationRequest
{
    protected function ability(): string
    {
        return 'guardian.scim.manage';
    }

    public function rules(): array
    {
        return CreateScimTokenData::rules();
    }

    /**
     * The token is in the response once.
     */
    public function persist(): JsonResponse
    {
        $issued = app(CreateScimTokenAction::class)->execute($this->organization(), CreateScimTokenData::fromValidated($this->validated()), $this->actor());

        return (new ScimTokenResource($issued->token))->additional(['token' => $issued->plain])->response()->setStatusCode(201);
    }
}
