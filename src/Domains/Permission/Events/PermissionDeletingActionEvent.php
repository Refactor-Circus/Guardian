<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A permission is about to be deleted.
 */
final class PermissionDeletingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PermissionModel $permission,
    ) {}
}
