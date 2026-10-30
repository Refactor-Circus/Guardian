<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoIdentityUnlinkedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoIdentityUnlinkingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Keystone\Actions\Action;

final class UnlinkSsoIdentityAction extends Action
{
    /**
     * Unlink an identity; signing in through that provider would then go
     * through the linking rules again.
     */
    protected function handle(SsoIdentityModel $identity): void
    {
        SsoIdentityUnlinkingActionEvent::dispatch($identity);

        DB::transaction(fn () => $identity->delete());

        SsoIdentityUnlinkedActionEvent::dispatch($identity);
    }
}
