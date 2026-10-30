<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A SCIM token has been issued.
 */
final class ScimTokenCreatedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ScimTokenModel $token,
    ) {}
}
