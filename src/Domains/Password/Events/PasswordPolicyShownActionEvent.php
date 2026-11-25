<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Password\Data\PasswordPolicySettings;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * The password policy was described.
 */
final class PasswordPolicyShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PasswordPolicySettings $settings,
    ) {}
}
