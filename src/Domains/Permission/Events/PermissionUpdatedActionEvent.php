<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A permission has been changed.
 */
final class PermissionUpdatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PermissionModel $permission,
    ) {}
}
