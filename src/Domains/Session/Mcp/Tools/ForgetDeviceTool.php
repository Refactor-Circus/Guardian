<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Guardian\Domains\Session\Mcp\Requests\ForgetDeviceMcpRequest;
use RefactorCircus\Keystone\Mcp\Tool;

#[Description('Forget one of your devices and revoke its sessions; the next sign-in from it counts as a new device.')]
final class ForgetDeviceTool extends Tool
{
    public function handle(ForgetDeviceMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'device' => $schema->string()->description('The device id.')->required(),
        ];
    }
}
