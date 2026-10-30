<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

/**
 * Someone is about to sign in through SSO.
 */
final class SsoLoginStartingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoConnectionModel $connection,
    ) {}
}
