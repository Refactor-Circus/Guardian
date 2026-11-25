<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Actions\ListDevicesAction;
use RefactorCircus\Guardian\Domains\Session\Data\ListDevicesData;
use RefactorCircus\Guardian\Domains\Session\Resources\DeviceResource;

final class IndexDevicesRequest extends OwnSessionRequest
{
    public function rules(): array
    {
        return ListDevicesData::rules();
    }

    public function persist(): JsonResponse
    {
        return DeviceResource::collection(app(ListDevicesAction::class)->execute($this->signedIn(), ListDevicesData::fromValidated($this->validated())))->response();
    }
}
