<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\SendChallengeCodeAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\SendChallengeCodeData;

final class SendChallengeCodeRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return SendChallengeCodeData::rules();
    }

    public function persist(): JsonResponse
    {
        app(SendChallengeCodeAction::class)->execute($this->signedIn(), SendChallengeCodeData::fromValidated($this->validated()));

        return new JsonResponse(null, 204);
    }
}
