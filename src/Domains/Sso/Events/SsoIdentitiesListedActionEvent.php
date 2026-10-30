<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * SSO identities have been listed.
 */
final class SsoIdentitiesListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListSsoIdentitiesData $filters,
    ) {}
}
