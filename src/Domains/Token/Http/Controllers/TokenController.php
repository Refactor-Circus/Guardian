<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\DestroyTokenRequest;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\DestroyUserTokensRequest;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\IndexTokensRequest;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\IndexUserTokensRequest;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\StoreTokenRequest;

final class TokenController
{
    public function index(IndexTokensRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreTokenRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyTokenRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function indexForUser(IndexUserTokensRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroyForUser(DestroyUserTokensRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
