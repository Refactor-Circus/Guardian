<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Data\ListRolesData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * Roles are about to be listed.
 */
final class RolesListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListRolesData $filters,
    ) {}
}
