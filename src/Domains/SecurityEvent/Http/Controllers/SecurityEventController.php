<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\CursorPaginator;
use RefactorCircus\Guardian\Domains\SecurityEvent\Http\Requests\IndexMySecurityEventsRequest;
use RefactorCircus\Guardian\Domains\SecurityEvent\Http\Requests\IndexSecurityEventsRequest;
use RefactorCircus\Guardian\Domains\SecurityEvent\Http\Requests\ShowSecurityEventRequest;
use RefactorCircus\Guardian\Domains\SecurityEvent\Resources\SecurityEventResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class SecurityEventController
{
    /**
     * List security events, newest first, a cursor page at a time.
     *
     * @response AnonymousResourceCollection<CursorPaginator<SecurityEventResource>>
     */
    public function index(IndexSecurityEventsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Show a security event.
     *
     * @response SecurityEventResource
     */
    public function show(ShowSecurityEventRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * List the caller's own security events, newest first.
     *
     * @response AnonymousResourceCollection<CursorPaginator<SecurityEventResource>>
     */
    public function mine(IndexMySecurityEventsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
