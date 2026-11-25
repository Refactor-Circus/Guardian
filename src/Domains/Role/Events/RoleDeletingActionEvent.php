<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A role is about to be deleted, with every assignment of it.
 */
final class RoleDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RoleModel $role,
    ) {}
}
