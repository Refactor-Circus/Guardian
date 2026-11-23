<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RemoveTwoFactorMethodAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;

final class DestroyTwoFactorMethodRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return RemoveTwoFactorMethodData::rules();
    }

    public function persist(): JsonResponse
    {
        app(RemoveTwoFactorMethodAction::class)->execute($this->signedIn(), $this->ownMethod(), RemoveTwoFactorMethodData::fromValidated($this->validated()));

        return new JsonResponse(null, 204);
    }
}
