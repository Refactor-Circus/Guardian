<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * A SCIM token is about to be issued.
 */
final class ScimTokenCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        public CreateScimTokenData $data,
    ) {}
}
