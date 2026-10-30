<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;

/**
 * An identity is about to be unlinked.
 */
final class SsoIdentityUnlinkingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoIdentityModel $identity,
    ) {}
}
