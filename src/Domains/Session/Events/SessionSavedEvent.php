<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The SessionModel `saved` Eloquent event.
 */
final class SessionSavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public SessionModel $session) {}

    public function model(): Model
    {
        return $this->session;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
