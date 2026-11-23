<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\DestroyDeviceRequest;
use RefactorCircus\Guardian\Domains\Session\Http\Requests\IndexDevicesRequest;

final class DeviceController
{
    public function index(IndexDevicesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DestroyDeviceRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
