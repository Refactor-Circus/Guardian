<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\ConfirmOneTimeCodeRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\ConfirmTotpRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\DestroyTwoFactorMethodRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\DestroyUserTwoFactorRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\ShowTwoFactorRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StoreOneTimeCodeRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StoreRecoveryCodesRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StoreTotpRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\ConfirmedFactorResource;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\RecoveryCodesResource;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TotpEnrolmentResource;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorMethodResource;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorStatusResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class TwoFactorController
{
    /**
     * Show the caller's two-factor status.
     *
     * @response TwoFactorStatusResource
     */
    public function show(ShowTwoFactorRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Start enrolling an authenticator app (TOTP).
     *
     * @response JsonResponse<ResourceResponse<TotpEnrolmentResource>, 201>
     */
    public function storeTotp(StoreTotpRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Confirm an authenticator app with a code.
     *
     * @response ConfirmedFactorResource
     */
    public function confirmTotp(ConfirmTotpRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Start enrolling an email address or phone number.
     *
     * @response JsonResponse<ResourceResponse<TwoFactorMethodResource>, 201>
     */
    public function storeOneTimeCode(StoreOneTimeCodeRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Confirm an email or SMS factor with the code sent.
     *
     * @response ConfirmedFactorResource
     */
    public function confirmOneTimeCode(ConfirmOneTimeCodeRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Regenerate the recovery codes.
     *
     * @response JsonResponse<ResourceResponse<RecoveryCodesResource>, 201>
     */
    public function storeRecoveryCodes(StoreRecoveryCodesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Remove a two-factor method.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroyMethod(DestroyTwoFactorMethodRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Reset a user's two-factor.
     *
     * @response JsonResponse<null, 204>
     */
    public function reset(DestroyUserTwoFactorRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
