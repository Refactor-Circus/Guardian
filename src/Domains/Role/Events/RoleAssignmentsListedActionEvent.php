<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * Role assignments have been listed.
 */
final class RoleAssignmentsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListRoleAssignmentsData $filters,
    ) {}
}
