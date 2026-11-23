<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * The browsers users sign in from (criterion 37): a device is a user's
 * user agent, kept as an HMAC keyed by the app key, with a readable name
 * cut from it.
 */
final class Devices
{
    /**
     * Note a request's device for the user: the device, and whether it was
     * seen for the first time.
     *
     * @return array{0: DeviceModel, 1: bool}
     */
    public function seen(Authenticatable $user, Request $request): array
    {
        $userAgent = (string) $request->userAgent();

        $device = DeviceModel::query()->createOrFirst(
            ['user_id' => UserKeys::of($user), 'fingerprint_hash' => $this->fingerprint($userAgent)],
            ['name' => $userAgent === '' ? null : Str::limit($userAgent, 255, ''), 'first_seen_at' => now(), 'last_seen_at' => now(), 'last_ip' => $request->ip()],
        );

        if (! $device->wasRecentlyCreated) {
            $device->forceFill(['last_seen_at' => now(), 'last_ip' => $request->ip()])->save();
        }

        return [$device, $device->wasRecentlyCreated];
    }

    /**
     * Whether the user has a device other than this one.
     */
    public function hasOthers(Authenticatable $user, DeviceModel $device): bool
    {
        return DeviceModel::query()->ownedBy(UserKeys::of($user))->whereKeyNot($device->getKey())->exists();
    }

    public function fingerprint(string $userAgent): string
    {
        $key = config('app.key');

        return hash_hmac('sha256', $userAgent, is_string($key) ? $key : '');
    }
}
