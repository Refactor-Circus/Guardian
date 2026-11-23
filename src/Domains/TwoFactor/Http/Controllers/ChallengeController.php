<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\PassChallengeRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\PasskeyChallengeOptionsRequest;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests\SendChallengeCodeRequest;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class ChallengeController
{
    /**
     * Pass the session's two-factor challenge.
     *
     * @response JsonResponse<null, 204>
     */
    public function pass(PassChallengeRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Send an email or SMS code for the challenge.
     *
     * @response JsonResponse<null, 204>
     */
    public function send(SendChallengeCodeRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Start a passkey assertion for the challenge.
     *
     * @response JsonResponse<array{data: array<string, mixed>}, 200>
     */
    public function passkeyOptions(PasskeyChallengeOptionsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
