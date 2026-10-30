<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Actions;

use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionShowingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionShownActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Keystone\Actions\Action;

final class ShowSsoConnectionAction extends Action
{
    protected function handle(SsoConnectionModel $connection): SsoConnectionModel
    {
        SsoConnectionShowingActionEvent::dispatch($connection);

        $connection->loadMissing('organization')->loadCount('identities');

        SsoConnectionShownActionEvent::dispatch($connection);

        return $connection;
    }
}
