<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ListTwoFactorPoliciesData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * The two-factor policies are about to be listed.
 */
final class TwoFactorPoliciesListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListTwoFactorPoliciesData $filters,
    ) {}
}
