<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\DestroyPasskeyRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\IndexPasskeysRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\PasskeyOptionsRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StorePasskeyRequest;

final class PasskeyController
{
    public function options(PasskeyOptionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function index(IndexPasskeysRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StorePasskeyRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyPasskeyRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
