<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Notifications\NewDeviceSignInNotification;
use RefactorCircus\Guardian\Domains\Session\Services\Devices;
use RefactorCircus\Guardian\Domains\Session\Services\Sessions;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;

/**
 * On sign-in (criterion 37): notes the device and starts the session's
 * record (when the sign-in has a session). The first sign-in from a device
 * by a user who already has another records `device.new` and tells the
 * user. On sign-out the record is closed.
 */
final class TrackSignIn
{
    public function __construct(
        private readonly Repository $config,
        private readonly Devices $devices,
        private readonly Sessions $sessions,
        private readonly SecurityLog $log,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'login']);
        $events->listen(Logout::class, [self::class, 'logout']);
    }

    public function login(Login $event): void
    {
        $request = $this->request();

        if ($request === null) {
            return;
        }

        [$device, $new] = $this->devices->seen($event->user, $request);

        if ($request->hasSession()) {
            $this->sessions->start($event->user, $request, $device);
        }

        if ($new && $this->devices->hasOthers($event->user, $device)) {
            $this->newDevice($event->user, $device, $request);
        }
    }

    public function logout(Logout $event): void
    {
        $request = $this->request();

        if ($request !== null && $request->hasSession()) {
            $this->sessions->end($request->session());
        }
    }

    /**
     * A sign-in from a device new to a user who has others.
     */
    private function newDevice(Authenticatable $user, DeviceModel $device, Request $request): void
    {
        $this->log->record(SecurityEventType::DeviceNew, $user, ['device' => $device->id]);

        if ($this->config->get('guardian.devices.notify', true) !== false && method_exists($user, 'notify')) {
            $user->notify(new NewDeviceSignInNotification($device->name, $request->ip(), now()->toImmutable()));
        }
    }

    private function request(): ?Request
    {
        $request = app()->bound('request') ? app('request') : null;

        return $request instanceof Request ? $request : null;
    }
}
