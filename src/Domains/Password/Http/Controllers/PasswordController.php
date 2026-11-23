<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Password\Http\Requests\CheckPasswordRequest;
use RefactorCircus\Guardian\Domains\Password\Http\Requests\ShowPasswordPolicyRequest;
use RefactorCircus\Guardian\Domains\Password\Resources\PasswordPolicyResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class PasswordController
{
    /**
     * Show the password policy.
     *
     * @response PasswordPolicyResource
     */
    public function policy(ShowPasswordPolicyRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Check a password against the policy.
     *
     * @response JsonResponse<array{valid: bool, errors: list<string>}, 200>
     */
    public function check(CheckPasswordRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
