<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The PermissionModel `deleted` Eloquent event.
 */
final class PermissionDeletedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PermissionModel $permission) {}

    public function model(): Model
    {
        return $this->permission;
    }

    public function hook(): string
    {
        return 'deleted';
    }
}
