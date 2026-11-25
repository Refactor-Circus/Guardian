<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * Permissions are about to be listed.
 */
final class PermissionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListPermissionsData $filters,
    ) {}
}
