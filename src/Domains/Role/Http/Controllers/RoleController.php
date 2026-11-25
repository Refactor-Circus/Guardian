<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\DestroyRoleRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\IndexRolesRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\ShowRoleRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\StoreRoleRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\UpdateRoleRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class RoleController
{
    /**
     * List roles.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<RoleResource>>
     */
    public function index(IndexRolesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Create a role.
     *
     * @response JsonResponse<ResourceResponse<RoleResource>, 201>
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Show a role.
     *
     * @response RoleResource
     */
    public function show(ShowRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Update a role.
     *
     * @response RoleResource
     */
    public function update(UpdateRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Delete a role.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroyRoleRequest $request): Response
    {
        return $request->persist();
    }
}
