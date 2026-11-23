<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\DestroyRoleAssignmentRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\IndexRoleAssignmentsRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\StoreRoleAssignmentRequest;

final class RoleAssignmentController
{
    public function index(IndexRoleAssignmentsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreRoleAssignmentRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyRoleAssignmentRequest $request): Response
    {
        return $request->persist();
    }
}
