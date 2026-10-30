<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Feature\Scim\Fixtures\Billing;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * Another package's finished event, about a user Roster also knows.
 */
final readonly class InvoicePaidActionEvent implements ActionFinishedEvent
{
    public function __construct(public Model $user) {}
}
