<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A SCIM token is about to be revoked.
 */
final class ScimTokenRevokingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ScimTokenModel $token,
    ) {}
}
