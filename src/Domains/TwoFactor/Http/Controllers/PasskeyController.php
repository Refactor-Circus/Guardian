<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\DestroyPasskeyRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\IndexPasskeysRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\PasskeyOptionsRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StorePasskeyRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\ConfirmedFactorResource;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\PasskeyResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class PasskeyController
{
    /**
     * Start registering a passkey.
     *
     * @response JsonResponse<array{data: array<string, mixed>}, 200>
     */
    public function options(PasskeyOptionsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * List the caller's passkeys.
     *
     * @response AnonymousResourceCollection<PasskeyResource>
     */
    public function index(IndexPasskeysRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Register a passkey.
     *
     * @response JsonResponse<ResourceResponse<ConfirmedFactorResource>, 201>
     */
    public function store(StorePasskeyRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Remove one of the caller's passkeys.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroyPasskeyRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
