<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A user's recovery codes are about to be replaced.
 */
final class RecoveryCodesRegeneratingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
    ) {}
}
