<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\ListIpRulesData;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * The IP rules were listed.
 */
final class IpRulesListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListIpRulesData $filters,
    ) {}
}
