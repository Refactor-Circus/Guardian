<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Guardian\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;

/**
 * An SSO connection is about to be updated.
 */
final class SsoConnectionUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SsoConnectionModel $connection,
        public UpdateSsoConnectionData $data,
    ) {}
}
