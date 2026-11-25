<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\RecoveryCodeModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The RecoveryCodeModel `replicating` Eloquent event.
 */
final class RecoveryCodeReplicatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public RecoveryCodeModel $recoveryCode) {}

    public function model(): Model
    {
        return $this->recoveryCode;
    }

    public function hook(): string
    {
        return 'replicating';
    }
}
