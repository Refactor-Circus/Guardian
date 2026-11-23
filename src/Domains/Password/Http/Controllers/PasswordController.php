<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Password\Http\Requests\CheckPasswordRequest;
use RefactorCircus\Guardian\Domains\Password\Http\Requests\ShowPasswordPolicyRequest;

final class PasswordController
{
    public function policy(ShowPasswordPolicyRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function check(CheckPasswordRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
