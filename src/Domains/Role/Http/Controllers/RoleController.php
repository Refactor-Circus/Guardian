<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\DestroyRoleRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\IndexRolesRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\ShowRoleRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\StoreRoleRequest;
use RefactorCircus\Guardian\Domains\Role\Http\Requests\UpdateRoleRequest;

final class RoleController
{
    public function index(IndexRolesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateRoleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyRoleRequest $request): Response
    {
        return $request->persist();
    }
}
