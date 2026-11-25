<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The TwoFactorMethodModel `saved` Eloquent event.
 */
final class TwoFactorMethodSavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TwoFactorMethodModel $twoFactorMethod) {}

    public function model(): Model
    {
        return $this->twoFactorMethod;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
