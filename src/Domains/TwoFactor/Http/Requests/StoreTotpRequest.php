<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\StartTotpEnrolmentAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\StartTotpEnrolmentData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TotpEnrolmentResource;

final class StoreTotpRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return StartTotpEnrolmentData::rules();
    }

    public function persist(): JsonResponse
    {
        $enrolment = app(StartTotpEnrolmentAction::class)->execute($this->signedIn(), StartTotpEnrolmentData::fromValidated($this->validated()));

        return (new TotpEnrolmentResource($enrolment))->response()->setStatusCode(201);
    }
}
