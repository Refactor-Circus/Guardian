<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

/**
 * An SSO connection has been shown.
 */
final class SsoConnectionShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoConnectionModel $connection,
    ) {}
}
