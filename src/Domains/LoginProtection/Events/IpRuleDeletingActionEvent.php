<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * An IP rule is about to be deleted.
 */
final class IpRuleDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public IpRuleModel $rule,
    ) {}
}
