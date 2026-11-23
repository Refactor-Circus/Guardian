<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\CreateIpRuleData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * An IP rule is about to be created.
 */
final class IpRuleCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CreateIpRuleData $data,
    ) {}
}
