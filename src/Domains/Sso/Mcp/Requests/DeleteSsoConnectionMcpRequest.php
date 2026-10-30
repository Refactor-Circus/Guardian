<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\Sso\Actions\DeleteSsoConnectionAction;

final class DeleteSsoConnectionMcpRequest extends SsoConnectionMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.manage';
    }

    protected function handle(array $validated): Response
    {
        app(DeleteSsoConnectionAction::class)->execute($this->connection());

        return Response::text('SSO connection deleted.');
    }
}
