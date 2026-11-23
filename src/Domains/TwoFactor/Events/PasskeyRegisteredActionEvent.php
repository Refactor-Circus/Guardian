<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A passkey was registered. Carries the passkey, never its public key.
 */
final class PasskeyRegisteredActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PasskeyModel $passkey,
    ) {}
}
