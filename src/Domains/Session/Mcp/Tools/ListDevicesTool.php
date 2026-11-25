<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Session\Data\ListDevicesData;
use RefactorCircus\Guardian\Domains\Session\Mcp\Requests\ListDevicesMcpRequest;
use RefactorCircus\Guardian\Domains\Session\Resources\DeviceResource;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('List the devices you have signed in from, most recently seen first.')]
final class ListDevicesTool extends Tool
{
    public function handle(ListDevicesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return ListDevicesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return DeviceResource::collectionSchema($schema, ListDevicesData::pagination());
    }
}
