<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\DestroyDeviceRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\IndexDevicesRequest;
use RefactorCircus\Guardian\Domains\Session\Resources\DeviceResource;

/**
 * The `@response` types name the payload each request's `persist()` builds,
 * for the OpenAPI document (openapi.json): the first docblock line is the
 * operation's summary.
 */
final class DeviceController
{
    /**
     * List the caller's devices.
     *
     * @response AnonymousResourceCollection<LengthAwarePaginator<DeviceResource>>
     */
    public function index(IndexDevicesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    /**
     * Forget one of the caller's devices.
     *
     * @response JsonResponse<null, 204>
     */
    public function destroy(DestroyDeviceRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
