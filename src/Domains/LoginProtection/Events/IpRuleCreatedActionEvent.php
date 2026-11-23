<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\LoginProtection\Models\IpRuleModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * An IP rule was created.
 */
final class IpRuleCreatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public IpRuleModel $rule,
    ) {}
}
