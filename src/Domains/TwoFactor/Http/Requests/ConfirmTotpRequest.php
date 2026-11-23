<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ConfirmTotpAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmTotpData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\ConfirmedFactorResource;

final class ConfirmTotpRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return ConfirmTotpData::rules();
    }

    public function persist(): JsonResponse
    {
        return (new ConfirmedFactorResource(app(ConfirmTotpAction::class)->execute($this->signedIn(), ConfirmTotpData::fromValidated($this->validated()))))->response();
    }
}
