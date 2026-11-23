<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\DestroyTwoFactorPolicyRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\IndexTwoFactorPoliciesRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StoreTwoFactorPolicyRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\UpdateTwoFactorPolicyRequest;

final class TwoFactorPolicyController
{
    public function index(IndexTwoFactorPoliciesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreTwoFactorPolicyRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateTwoFactorPolicyRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyTwoFactorPolicyRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
