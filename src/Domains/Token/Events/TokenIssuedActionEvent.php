<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Token\Data\TokenSummary;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * An API token was issued (the event never carries its value).
 */
final class TokenIssuedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Authenticatable $user,
        public TokenSummary $token,
    ) {}
}
