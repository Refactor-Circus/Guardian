<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

/**
 * Someone signed in through SSO.
 */
final class SsoLoginSucceededActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public SsoConnectionModel $connection,
        public string $method,
    ) {}
}
