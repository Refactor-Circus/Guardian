<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\OneTimeCodeModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The OneTimeCodeModel `retrieved` Eloquent event.
 */
final class OneTimeCodeRetrievedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public OneTimeCodeModel $oneTimeCode) {}

    public function model(): Model
    {
        return $this->oneTimeCode;
    }

    public function hook(): string
    {
        return 'retrieved';
    }
}
