<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\DestroyOtherSessionsRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\DestroySessionRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\DestroyUserSessionsRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\IndexSessionsRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\IndexUserSessionsRequest;
use RefactorCircus\Guardian\Domains\Session\Resources\SessionResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class SessionController
{
    /**
     * List the caller's signed-in sessions.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<SessionResource>>
     */
    public function index(IndexSessionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Sign out one of the caller's sessions.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroySessionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Sign out the caller's other sessions, and optionally revoke their API tokens.
     *
     * @response JsonResponse<array{revoked: int, tokens_revoked: int|null}, 200>
     */
    public function destroyOthers(DestroyOtherSessionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * List a user's signed-in sessions.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<SessionResource>>
     */
    public function indexForUser(IndexUserSessionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Sign out every session of a user.
     *
     * @response JsonResponse<array{revoked: int}, 200>
     */
    public function destroyForUser(DestroyUserSessionsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
