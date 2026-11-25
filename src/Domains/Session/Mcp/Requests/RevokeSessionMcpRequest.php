<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\Session\Actions\RevokeSessionAction;

final class RevokeSessionMcpRequest extends OwnSessionMcpRequest
{
    protected function rules(): array
    {
        return ['session' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(RevokeSessionAction::class)->execute($this->ownSession(), $this->signedIn());

        return Response::text('Session revoked.');
    }
}
