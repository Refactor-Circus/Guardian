<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartPasskeyChallengeAction;

final class PasskeyChallengeOptionsRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return new JsonResponse(['data' => app(StartPasskeyChallengeAction::class)->execute($this->signedIn())]);
    }
}
