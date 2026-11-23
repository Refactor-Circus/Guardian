<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\PassChallengeRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\PasskeyChallengeOptionsRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\SendChallengeCodeRequest;

final class ChallengeController
{
    public function pass(PassChallengeRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function send(SendChallengeCodeRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function passkeyOptions(PasskeyChallengeOptionsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
