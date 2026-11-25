<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Session\Services\Devices;
use RefactorCircus\Guardian\Domains\Session\Services\Sessions;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use Symfony\Component\HttpFoundation\Response;

/**
 * The `guardian.session` middleware (criterion 36), for authenticated
 * routes with a session: it finds the session's record by the key the
 * session holds (starting one for a session signed in before tracking
 * began), touches its activity at most every
 * `guardian.sessions.touch_seconds`, and signs a revoked session out with a
 * 401 `session_revoked`. Guests and requests without a session pass.
 */
final class TrackSession
{
    public function __construct(
        private readonly Repository $config,
        private readonly Sessions $sessions,
        private readonly Devices $devices,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Authenticatable || ! $request->hasSession()) {
            return $next($request);
        }

        $record = $this->sessions->current($request->session());

        if ($record === null || (string) $record->user_id !== (string) UserKeys::of($user)) {
            $this->sessions->start($user, $request, $this->devices->seen($user, $request)[0]);

            return $next($request);
        }

        if ($record->revoked_at !== null) {
            return $this->signOut($request);
        }

        $this->touch($record, $request);

        return $next($request);
    }

    private function touch(SessionModel $record, Request $request): void
    {
        $seconds = $this->config->get('guardian.sessions.touch_seconds', 60);

        if ($record->last_active_at->diffInSeconds(now()) < (is_numeric($seconds) ? (int) $seconds : 60)) {
            return;
        }

        $record->forceFill(['last_active_at' => now(), 'ip' => $request->ip()])->save();
        $record->device?->forceFill(['last_seen_at' => now(), 'last_ip' => $request->ip()])->save();
    }

    /**
     * Sign every session guard out, end the session and refuse the request.
     */
    private function signOut(Request $request): JsonResponse
    {
        foreach ((array) $this->config->get('auth.guards', []) as $name => $guard) {
            if (is_string($name) && is_array($guard) && ($guard['driver'] ?? null) === 'session') {
                $stateful = Auth::guard($name);

                if ($stateful->check()) {
                    $stateful->logout();
                }
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse(['message' => __('guardian::guardian.session_revoked'), 'code' => 'session_revoked'], 401);
    }
}
