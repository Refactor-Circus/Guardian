<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RegisterPasskeyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RegisterPasskeyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\ConfirmedFactorResource;

final class StorePasskeyRequest extends OwnTwoFactorRequest
{
    /**
     * Without the library the answer is 501 before anything is validated.
     */
    protected function prepareForValidation(): void
    {
        app(PasskeysAvailable::class)->ensure();
    }

    public function rules(): array
    {
        return RegisterPasskeyData::rules();
    }

    public function persist(): JsonResponse
    {
        $this->ensureNoChallengeOwed();

        $registered = app(RegisterPasskeyAction::class)->execute($this->signedIn(), RegisterPasskeyData::fromValidated($this->validated()));
        $this->enrolled();

        return (new ConfirmedFactorResource($registered))->response()->setStatusCode(201);
    }
}
