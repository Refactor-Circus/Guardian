<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * The records of signed-in sessions (criteria 36 and 37).
 */
final class Sessions
{
    public function __construct(private readonly SessionKeys $keys) {}

    /**
     * Give the request's session a new key and a record for the user.
     */
    public function start(Authenticatable $user, Request $request, ?DeviceModel $device): SessionModel
    {
        $userAgent = $request->userAgent();

        return SessionModel::query()->create([
            'user_id' => UserKeys::of($user),
            'device_id' => $device?->id,
            'key_hash' => $this->keys->issue($request->session()),
            'ip' => $request->ip(),
            'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 512, ''),
            'last_active_at' => now(),
        ]);
    }

    /**
     * The record of the session, by the key it holds; null when it holds
     * none, or none is stored.
     */
    public function current(?Session $session): ?SessionModel
    {
        $hash = $this->keys->current($session);

        return $hash === null ? null : SessionModel::query()->where('key_hash', $hash)->first();
    }

    /**
     * The hash of the request's session key, to tell the current session
     * apart in lists; null without a session.
     */
    public function currentHash(?Request $request): ?string
    {
        return $request !== null && $request->hasSession() ? $this->keys->current($request->session()) : null;
    }

    /**
     * The session ended (a sign-out): its record is closed, without a
     * revocation being recorded, and the key forgotten.
     */
    public function end(Session $session): void
    {
        $record = $this->current($session);

        if ($record !== null && $record->revoked_at === null) {
            $record->forceFill(['revoked_at' => now()])->save();
        }

        $this->keys->forget($session);
    }
}
