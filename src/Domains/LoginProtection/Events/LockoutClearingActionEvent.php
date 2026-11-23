<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A sign-in lockout is about to be cleared (the event carries only the identifier's HMAC).
 */
final class LockoutClearingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $identifierHash,
    ) {}
}
