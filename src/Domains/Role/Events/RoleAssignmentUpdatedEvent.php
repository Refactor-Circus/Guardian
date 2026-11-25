<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The RoleAssignmentModel `updated` Eloquent event.
 */
final class RoleAssignmentUpdatedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public RoleAssignmentModel $roleAssignment) {}

    public function model(): Model
    {
        return $this->roleAssignment;
    }

    public function hook(): string
    {
        return 'updated';
    }
}
