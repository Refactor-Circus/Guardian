<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\DestroyOtherSessionsRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\DestroySessionRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\DestroyUserSessionsRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\IndexSessionsRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\IndexUserSessionsRequest;

final class SessionController
{
    public function index(IndexSessionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroySessionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroyOthers(DestroyOtherSessionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function indexForUser(IndexUserSessionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroyForUser(DestroyUserSessionsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
