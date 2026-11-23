<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A sign-in lockout was cleared (the event carries only the identifier's HMAC).
 */
final class LockoutClearedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $identifierHash,
    ) {}
}
