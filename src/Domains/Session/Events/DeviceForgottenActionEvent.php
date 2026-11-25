<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A device was forgotten and its sessions revoked.
 */
final class DeviceForgottenActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public DeviceModel $device,
    ) {}
}
