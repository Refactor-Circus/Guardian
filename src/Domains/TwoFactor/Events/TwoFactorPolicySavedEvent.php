<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The TwoFactorPolicyModel `saved` Eloquent event.
 */
final class TwoFactorPolicySavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TwoFactorPolicyModel $twoFactorPolicy) {}

    public function model(): Model
    {
        return $this->twoFactorPolicy;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
