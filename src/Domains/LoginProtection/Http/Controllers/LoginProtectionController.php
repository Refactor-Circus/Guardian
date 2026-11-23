<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests\DestroyIpRuleRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests\DestroyLockoutRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests\IndexIpRulesRequest;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Requests\StoreIpRuleRequest;

final class LoginProtectionController
{
    public function destroyLockout(DestroyLockoutRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function indexIpRules(IndexIpRulesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function storeIpRule(StoreIpRuleRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroyIpRule(DestroyIpRuleRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
