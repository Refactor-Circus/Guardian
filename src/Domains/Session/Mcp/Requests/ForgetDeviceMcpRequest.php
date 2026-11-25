<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\Session\Actions\ForgetDeviceAction;

final class ForgetDeviceMcpRequest extends OwnSessionMcpRequest
{
    protected function rules(): array
    {
        return ['device' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(ForgetDeviceAction::class)->execute($this->ownDevice(), $this->signedIn());

        return Response::text('Device forgotten.');
    }
}
