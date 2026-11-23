<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RemovePasskeyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;

final class DestroyPasskeyRequest extends OwnTwoFactorRequest
{
    /**
     * Without the library the answer is 501 before anything is looked up.
     */
    protected function prepareForValidation(): void
    {
        app(PasskeysAvailable::class)->ensure();
    }

    public function rules(): array
    {
        return RemoveTwoFactorMethodData::rules();
    }

    public function persist(): JsonResponse
    {
        app(RemovePasskeyAction::class)->execute($this->signedIn(), $this->ownPasskey(), RemoveTwoFactorMethodData::fromValidated($this->validated()));

        return new JsonResponse(null, 204);
    }
}
