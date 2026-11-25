<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests\DestroyIpRuleRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests\DestroyLockoutRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests\IndexIpRulesRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests\StoreIpRuleRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Resources\IpRuleResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class LoginProtectionController
{
    /**
     * Clear the login lockout of an identifier or address.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroyLockout(DestroyLockoutRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * List the IP allow and deny rules.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<IpRuleResource>>
     */
    public function indexIpRules(IndexIpRulesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Add an IP allow or deny rule.
     *
     * @response JsonResponse<ResourceResponse<IpRuleResource>, 201>
     */
    public function storeIpRule(StoreIpRuleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Delete an IP rule.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroyIpRule(DestroyIpRuleRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
