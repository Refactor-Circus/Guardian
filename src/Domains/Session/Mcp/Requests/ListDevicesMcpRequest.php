<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Session\Actions\ListDevicesAction;
use RefactorCircus\Guardian\Domains\Session\Data\ListDevicesData;
use RefactorCircus\Guardian\Domains\Session\Resources\DeviceResource;

final class ListDevicesMcpRequest extends OwnSessionMcpRequest
{
    protected function rules(): array
    {
        return ListDevicesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return DeviceResource::collection(app(ListDevicesAction::class)->execute($this->signedIn(), ListDevicesData::fromValidated($validated)))->toStructuredResponse();
    }
}
