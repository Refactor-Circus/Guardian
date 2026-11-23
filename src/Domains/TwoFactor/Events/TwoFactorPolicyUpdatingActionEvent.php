<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\UpdateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A two-factor policy is about to be changed.
 */
final class TwoFactorPolicyUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TwoFactorPolicyModel $policy,
        public UpdateTwoFactorPolicyData $data,
    ) {}
}
