<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Data\ListRolesData;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * Roles have been listed.
 */
final class RolesListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListRolesData $filters,
    ) {}
}
