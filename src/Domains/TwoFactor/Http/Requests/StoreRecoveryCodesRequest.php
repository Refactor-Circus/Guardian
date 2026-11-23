<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RegenerateRecoveryCodesAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\RecoveryCodesResource;

final class StoreRecoveryCodesRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return (new RecoveryCodesResource(app(RegenerateRecoveryCodesAction::class)->execute($this->signedIn())))->response()->setStatusCode(201);
    }
}
