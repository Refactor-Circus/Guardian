<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Data\ListUserAbilitiesData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A user's abilities in a scope are about to be listed.
 */
final class UserAbilitiesListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public ListUserAbilitiesData $filters,
    ) {}
}
