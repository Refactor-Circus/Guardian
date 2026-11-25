<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The PasskeyModel `saved` Eloquent event.
 */
final class PasskeySavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PasskeyModel $passkey) {}

    public function model(): Model
    {
        return $this->passkey;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
