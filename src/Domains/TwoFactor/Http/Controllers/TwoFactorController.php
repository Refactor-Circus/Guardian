<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\ConfirmTotpRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\DestroyTwoFactorMethodRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\DestroyUserTwoFactorRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\ShowTwoFactorRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StoreRecoveryCodesRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StoreTotpRequest;

final class TwoFactorController
{
    public function show(ShowTwoFactorRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function storeTotp(StoreTotpRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function confirmTotp(ConfirmTotpRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function storeRecoveryCodes(StoreRecoveryCodesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroyMethod(DestroyTwoFactorMethodRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function reset(DestroyUserTwoFactorRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
