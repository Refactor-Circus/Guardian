<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Token\Data\IssueTokenData;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * An API token is about to be issued (the event never carries its value).
 */
final class TokenIssuingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Authenticatable $user,
        public IssueTokenData $data,
    ) {}
}
