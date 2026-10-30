<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Guardian\Domains\Sso\Actions\DeleteSsoConnectionAction;

final class DestroySsoConnectionRequest extends SsoConnectionRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.manage';
    }

    public function persist(): Response
    {
        app(DeleteSsoConnectionAction::class)->execute($this->connection());

        return response()->noContent();
    }
}
