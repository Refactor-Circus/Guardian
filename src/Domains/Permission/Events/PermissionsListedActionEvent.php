<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * Permissions have been listed.
 */
final class PermissionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListPermissionsData $filters,
    ) {}
}
