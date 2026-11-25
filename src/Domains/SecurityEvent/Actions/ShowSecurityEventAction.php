<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Actions;

use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventShowingActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Events\SecurityEventShownActionEvent;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Keystone\Actions\Action;

final class ShowSecurityEventAction extends Action
{
    protected function handle(SecurityEventModel $securityEvent): SecurityEventModel
    {
        SecurityEventShowingActionEvent::dispatch($securityEvent);
        SecurityEventShownActionEvent::dispatch($securityEvent);

        return $securityEvent;
    }
}
