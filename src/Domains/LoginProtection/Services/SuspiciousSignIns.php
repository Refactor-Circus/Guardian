<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\LoginProtection\Notifications\SuspiciousSignInNotification;
use RefactorCircus\Guardian\Domains\LoginProtection\Support\Identifier;
use RefactorCircus\Guardian\Domains\LoginProtection\Support\Networks;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * Suspicious sign-ins (criterion 51, D-016): a sign-in from a device new to
 * a user who has another is suspicious when the identifier failed three or
 * more times in the past hour, or the address's network (/24, /48) was never
 * seen for the user. It is recorded as `login.suspicious` and the user is
 * told; the sign-in is never blocked.
 */
final class SuspiciousSignIns
{
    public const int FAILURES = 3;

    public function __construct(
        private readonly LoginFailures $failures,
        private readonly SecurityLog $log,
    ) {}

    /**
     * Handle a sign-in from a new device: true when it was suspicious (and
     * recorded and notified), false to leave it to the new-device notice.
     */
    public function check(Authenticatable $user, Request $request, DeviceModel $device, ?SessionModel $session): bool
    {
        $identifier = Identifier::of($user);
        $failures = $identifier === null ? 0 : $this->failures->recent($identifier);
        $newNetwork = $this->newNetwork($user, $request->ip(), $device, $session);

        if ($failures < self::FAILURES && ! $newNetwork) {
            return false;
        }

        $this->log->record(SecurityEventType::LoginSuspicious, $user, [
            'device' => $device->id,
            'recent_failures' => $failures,
            'new_network' => $newNetwork,
        ]);

        if (method_exists($user, 'notify')) {
            $user->notify(new SuspiciousSignInNotification($device->name, $request->ip(), now()->toImmutable(), $failures, $newNetwork));
        }

        return true;
    }

    /**
     * Whether no other session or device of the user was ever seen on the
     * address's network.
     */
    private function newNetwork(Authenticatable $user, ?string $ip, DeviceModel $device, ?SessionModel $session): bool
    {
        $network = Networks::of($ip);

        if ($network === null) {
            return false;
        }

        $key = UserKeys::of($user);

        $seen = SessionModel::query()->where('user_id', $key)->when($session !== null, fn (Builder $query): Builder => $query->whereKeyNot($session?->getKey()))->whereNotNull('ip')->distinct()->pluck('ip')
            ->merge(DeviceModel::query()->ownedBy($key)->whereKeyNot($device->getKey())->whereNotNull('last_ip')->distinct()->pluck('last_ip'));

        return ! $seen->contains(fn (mixed $known): bool => is_string($known) && Networks::of($known) === $network);
    }
}
