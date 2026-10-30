<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\DestroySsoConnectionRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\DestroySsoIdentityRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\IndexSsoConnectionsRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\IndexUserSsoIdentitiesRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\ShowSsoConnectionRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\StoreSsoConnectionRequest;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\UpdateSsoConnectionRequest;

final class SsoController
{
    public function index(IndexSsoConnectionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateSsoConnectionRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroySsoConnectionRequest $request): Response
    {
        return $request->persist();
    }

    public function identities(IndexUserSsoIdentitiesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function unlink(DestroySsoIdentityRequest $request): Response
    {
        return $request->persist();
    }
}
