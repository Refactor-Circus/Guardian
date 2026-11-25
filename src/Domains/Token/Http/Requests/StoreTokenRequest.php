<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Token\Actions\IssueTokenAction;
use RefactorCircus\Guardian\Domains\Token\Data\IssueTokenData;
use RefactorCircus\Guardian\Domains\Token\Resources\TokenResource;

/**
 * Issue an API token to the caller: the plain-text value is in this answer
 * only.
 */
final class StoreTokenRequest extends OwnTokenRequest
{
    public function rules(): array
    {
        return IssueTokenData::rules();
    }

    public function persist(): JsonResponse
    {
        $issued = app(IssueTokenAction::class)->execute($this->signedIn(), IssueTokenData::fromValidated($this->validated()));

        return (new TokenResource($issued->token))->additional(['plain_text_token' => $issued->plainTextToken])->response()->setStatusCode(201);
    }
}
