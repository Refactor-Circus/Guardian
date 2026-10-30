<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The ScimGroupModel `saving` Eloquent event.
 */
final class ScimGroupSavingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ScimGroupModel $scimGroup) {}

    public function model(): Model
    {
        return $this->scimGroup;
    }

    public function hook(): string
    {
        return 'saving';
    }
}
