<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The ScimUserModel `saving` Eloquent event.
 */
final class ScimUserSavingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ScimUserModel $scimUser) {}

    public function model(): Model
    {
        return $this->scimUser;
    }

    public function hook(): string
    {
        return 'saving';
    }
}
