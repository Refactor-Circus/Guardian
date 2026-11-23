<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ConfirmOneTimeCodeAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmOneTimeCodeData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\ConfirmedFactorResource;

final class ConfirmOneTimeCodeRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return ConfirmOneTimeCodeData::rules();
    }

    public function persist(): JsonResponse
    {
        $this->ensureNoChallengeOwed();

        $confirmed = app(ConfirmOneTimeCodeAction::class)->execute($this->signedIn(), ConfirmOneTimeCodeData::fromValidated($this->validated()));
        $this->enrolled();

        return (new ConfirmedFactorResource($confirmed))->response();
    }
}
