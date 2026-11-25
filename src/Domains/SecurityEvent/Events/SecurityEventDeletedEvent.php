<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The SecurityEventModel `deleted` Eloquent event.
 */
final class SecurityEventDeletedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public SecurityEventModel $securityEvent) {}

    public function model(): Model
    {
        return $this->securityEvent;
    }

    public function hook(): string
    {
        return 'deleted';
    }
}
