<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\CreateTwoFactorPolicyData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A two-factor policy is about to be created.
 */
final class TwoFactorPolicyCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CreateTwoFactorPolicyData $data,
    ) {}
}
