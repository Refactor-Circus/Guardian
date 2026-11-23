<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ListTwoFactorPoliciesData;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * The two-factor policies were listed.
 */
final class TwoFactorPoliciesListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListTwoFactorPoliciesData $filters,
    ) {}
}
