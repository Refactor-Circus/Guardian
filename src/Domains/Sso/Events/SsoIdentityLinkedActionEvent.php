<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

/**
 * An identity has been linked to a user.
 */
final class SsoIdentityLinkedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public SsoConnectionModel $connection,
    ) {}
}
