<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Sso\Actions\ShowSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource;

final class ShowSsoConnectionMcpRequest extends SsoConnectionMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.view';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new SsoConnectionResource(app(ShowSsoConnectionAction::class)->execute($this->connection())))->toStructuredResponse();
    }
}
