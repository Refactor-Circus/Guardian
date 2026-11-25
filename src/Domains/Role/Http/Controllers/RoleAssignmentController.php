<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\DestroyRoleAssignmentRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\IndexRoleAssignmentsRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\StoreRoleAssignmentRequest;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleAssignmentResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class RoleAssignmentController
{
    /**
     * List role assignments.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<RoleAssignmentResource>>
     */
    public function index(IndexRoleAssignmentsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Assign a role to a user, optionally within a scope.
     *
     * @response JsonResponse<ResourceResponse<RoleAssignmentResource>, 201>
     */
    public function store(StoreRoleAssignmentRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Revoke a role assignment.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroyRoleAssignmentRequest $request): Response
    {
        return $request->persist();
    }
}
