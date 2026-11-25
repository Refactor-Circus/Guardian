<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\DestroyTokenRequest;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\DestroyUserTokensRequest;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\IndexTokensRequest;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\IndexUserTokensRequest;
use RefactorCircus\Guardian\Domains\Token\Http\Requests\StoreTokenRequest;
use RefactorCircus\Guardian\Domains\Token\Resources\TokenResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class TokenController
{
    /**
     * List the caller's API tokens.
     *
     * @response AnonymousResourceCollection<TokenResource>
     */
    public function index(IndexTokensRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Issue an API token; its value is answered once.
     *
     * @response JsonResponse<array{data: TokenResource, plain_text_token: string}, 201>
     */
    public function store(StoreTokenRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Revoke one of the caller's API tokens.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroyTokenRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * List a user's API tokens.
     *
     * @response AnonymousResourceCollection<TokenResource>
     */
    public function indexForUser(IndexUserTokensRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Revoke every API token of a user.
     *
     * @response JsonResponse<array{revoked: int}, 200>
     */
    public function destroyForUser(DestroyUserTokensRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
