<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * SSO connections are about to be listed.
 */
final class SsoConnectionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListSsoConnectionsData $filters,
    ) {}
}
