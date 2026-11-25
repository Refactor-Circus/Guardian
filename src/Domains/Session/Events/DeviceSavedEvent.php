<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

/**
 * The DeviceModel `saved` Eloquent event.
 */
final class DeviceSavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public DeviceModel $device) {}

    public function model(): Model
    {
        return $this->device;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
