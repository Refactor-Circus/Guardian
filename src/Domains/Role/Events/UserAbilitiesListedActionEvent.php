<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Role\Data\UserAbilities;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A user's abilities in a scope have been listed.
 */
final class UserAbilitiesListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public UserAbilities $abilities,
    ) {}
}
