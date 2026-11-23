<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\DestroySsoConnectionRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\DestroySsoIdentityRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\IndexSsoConnectionsRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\IndexUserSsoIdentitiesRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\ShowSsoConnectionRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\StoreSsoConnectionRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\UpdateSsoConnectionRequest;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoIdentityResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class SsoController
{
    /**
     * List an organization's SSO connections.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<SsoConnectionResource>>
     */
    public function index(IndexSsoConnectionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Create an SSO connection for an organization.
     *
     * @response JsonResponse<ResourceResponse<SsoConnectionResource>, 201>
     */
    public function store(StoreSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Show an SSO connection.
     *
     * @response SsoConnectionResource
     */
    public function show(ShowSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Update an SSO connection.
     *
     * @response SsoConnectionResource
     */
    public function update(UpdateSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Delete an SSO connection.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroySsoConnectionRequest $request): Response
    {
        return $request->persist();
    }

    /**
     * List a user's linked SSO identities.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<SsoIdentityResource>>
     */
    public function identities(IndexUserSsoIdentitiesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Unlink an SSO identity.
     *
     * @response JsonResponse<null, 204>
     */
    public function unlink(DestroySsoIdentityRequest $request): Response
    {
        return $request->persist();
    }
}
