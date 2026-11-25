<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A role has been shown.
 */
final class RoleShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public RoleModel $role,
    ) {}
}
