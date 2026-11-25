<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Password\Models\PasswordHistoryModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The PasswordHistoryModel `creating` Eloquent event.
 */
final class PasswordHistoryCreatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PasswordHistoryModel $passwordHistory) {}

    public function model(): Model
    {
        return $this->passwordHistory;
    }

    public function hook(): string
    {
        return 'creating';
    }
}
