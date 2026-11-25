<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\DestroyTwoFactorPolicyRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\IndexTwoFactorPoliciesRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\StoreTwoFactorPolicyRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\UpdateTwoFactorPolicyRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class TwoFactorPolicyController
{
    /**
     * List two-factor policies.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<TwoFactorPolicyResource>>
     */
    public function index(IndexTwoFactorPoliciesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Create a two-factor policy.
     *
     * @response JsonResponse<ResourceResponse<TwoFactorPolicyResource>, 201>
     */
    public function store(StoreTwoFactorPolicyRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Update a two-factor policy.
     *
     * @response TwoFactorPolicyResource
     */
    public function update(UpdateTwoFactorPolicyRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Delete a two-factor policy.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroyTwoFactorPolicyRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
