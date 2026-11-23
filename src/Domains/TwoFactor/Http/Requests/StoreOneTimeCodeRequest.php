<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartOneTimeCodeEnrolmentAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\StartOneTimeCodeEnrolmentData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorMethodResource;

final class StoreOneTimeCodeRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return StartOneTimeCodeEnrolmentData::rules();
    }

    public function persist(): JsonResponse
    {
        $this->ensureNoChallengeOwed();

        $method = app(StartOneTimeCodeEnrolmentAction::class)->execute($this->signedIn(), StartOneTimeCodeEnrolmentData::fromValidated($this->validated()));

        return (new TwoFactorMethodResource($method))->response()->setStatusCode(201);
    }
}
