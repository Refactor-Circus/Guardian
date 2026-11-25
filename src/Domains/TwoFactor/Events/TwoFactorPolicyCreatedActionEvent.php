<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A two-factor policy was created.
 */
final class TwoFactorPolicyCreatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TwoFactorPolicyModel $policy,
    ) {}
}
