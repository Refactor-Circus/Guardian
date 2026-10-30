<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Sso\Actions\ShowSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource;

final class ShowSsoConnectionRequest extends SsoConnectionRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.view';
    }

    public function persist(): JsonResponse
    {
        return (new SsoConnectionResource(app(ShowSsoConnectionAction::class)->execute($this->connection())))->response();
    }
}
