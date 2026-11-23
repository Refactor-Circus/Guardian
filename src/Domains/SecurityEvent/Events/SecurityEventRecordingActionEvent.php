<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A security event is about to be recorded. It carries only the type and the user: never the identifier typed or the context.
 */
final class SecurityEventRecordingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SecurityEventType $type,
        public ?string $user,
    ) {}
}
