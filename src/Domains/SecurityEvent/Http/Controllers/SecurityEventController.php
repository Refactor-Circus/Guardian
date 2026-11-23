<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\SecurityEvent\Http\Requests\IndexMySecurityEventsRequest;
use RefactorCircus\Guardian\Domains\SecurityEvent\Http\Requests\IndexSecurityEventsRequest;
use RefactorCircus\Guardian\Domains\SecurityEvent\Http\Requests\ShowSecurityEventRequest;

final class SecurityEventController
{
    public function index(IndexSecurityEventsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowSecurityEventRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function mine(IndexMySecurityEventsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
