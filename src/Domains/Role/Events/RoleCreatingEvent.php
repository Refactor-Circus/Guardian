<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The RoleModel `creating` Eloquent event.
 */
final class RoleCreatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public RoleModel $role) {}

    public function model(): Model
    {
        return $this->role;
    }

    public function hook(): string
    {
        return 'creating';
    }
}
