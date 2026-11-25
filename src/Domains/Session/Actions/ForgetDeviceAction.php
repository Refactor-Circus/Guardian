<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Session\Events\DeviceForgettingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\DeviceForgottenActionEvent;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Session\Services\Revocations;
use RefactorCircus\Keystone\Actions\Action;

final class ForgetDeviceAction extends Action
{
    public function __construct(private readonly Revocations $revocations) {}

    /**
     * Forget a device (criterion 38): its active sessions are revoked (each
     * recorded as `session.revoked`), its sessions let go of it, and it is
     * deleted, so the next sign-in from it counts as a new device.
     */
    protected function handle(DeviceModel $device, ?Authenticatable $actor = null): void
    {
        DeviceForgettingActionEvent::dispatch($device);

        DB::transaction(function () use ($device, $actor): void {
            $this->revocations->revoke(SessionModel::query()->where('device_id', $device->id)->whereNull('revoked_at')->get(), $actor, ['device' => $device->id]);

            SessionModel::query()->where('device_id', $device->id)->update(['device_id' => null]);

            $device->delete();
        });

        DeviceForgottenActionEvent::dispatch($device);
    }
}
