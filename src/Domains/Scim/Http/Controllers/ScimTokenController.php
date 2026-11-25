<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Scim\Http\Requests\IndexScimTokensRequest;
use RefactorCircus\Guardian\Domains\Scim\Http\Requests\RevokeScimTokenRequest;
use RefactorCircus\Guardian\Domains\Scim\Http\Requests\StoreScimTokenRequest;
use RefactorCircus\Guardian\Domains\Scim\Resources\ScimTokenResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class ScimTokenController
{
    /**
     * List an organization's SCIM tokens.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<ScimTokenResource>>
     */
    public function index(IndexScimTokensRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Create a SCIM token; its value is answered once.
     *
     * @response JsonResponse<array{data: ScimTokenResource, token: string}, 201>
     */
    public function store(StoreScimTokenRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Revoke a SCIM token.
     *
     * @response ScimTokenResource
     */
    public function destroy(RevokeScimTokenRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
