<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Permission\Http\Requests\DestroyPermissionRequest;
use RefactorCircus\Guardian\Domains\Permission\Http\Requests\IndexPermissionsRequest;
use RefactorCircus\Guardian\Domains\Permission\Http\Requests\StorePermissionRequest;
use RefactorCircus\Guardian\Domains\Permission\Http\Requests\UpdatePermissionRequest;
use RefactorCircus\Guardian\Domains\Permission\Resources\PermissionResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class PermissionController
{
    /**
     * List permissions.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<PermissionResource>>
     */
    public function index(IndexPermissionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Create a permission.
     *
     * @response JsonResponse<ResourceResponse<PermissionResource>, 201>
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Update a permission.
     *
     * @response PermissionResource
     */
    public function update(UpdatePermissionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Delete a permission.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroyPermissionRequest $request): Response
    {
        return $request->persist();
    }
}
