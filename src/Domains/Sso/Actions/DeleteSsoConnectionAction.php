<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionDeletedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionDeletingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Keystone\Actions\Action;

final class DeleteSsoConnectionAction extends Action
{
    /**
     * Remove a connection and its linked identities. Accounts stay.
     */
    protected function handle(SsoConnectionModel $connection): void
    {
        SsoConnectionDeletingActionEvent::dispatch($connection);

        DB::transaction(fn () => $connection->delete());

        SsoConnectionDeletedActionEvent::dispatch($connection);
    }
}
