<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Http\Middleware;

use Closure;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\LoginProtection\Services\LoginFailures;
use RefactorCircus\Guardian\Domains\LoginProtection\Support\Identifier;
use Symfony\Component\HttpFoundation\Response;

/**
 * The `guardian.login-throttle` middleware for the host's login route
 * (criterion 49): once the identifier typed has failed
 * `guardian.login.max_attempts` times within `decay_minutes`, or the address
 * `ip_max_attempts` times, the attempt is refused with a 429
 * `{message, retry_after}` and `Retry-After`, and Laravel's `Lockout` fires
 * (recorded as `login.locked_out`).
 */
final class ThrottleLogins
{
    public function __construct(private readonly LoginFailures $failures) {}

    public function handle(Request $request, Closure $next): Response
    {
        $retryAfter = $this->failures->lockedOutFor(Identifier::in($request->all()), $request->ip());

        if ($retryAfter === null) {
            return $next($request);
        }

        event(new Lockout($request));

        return new JsonResponse([
            'message' => __('auth.throttle', ['seconds' => $retryAfter, 'minutes' => (int) ceil($retryAfter / 60)]),
            'retry_after' => $retryAfter,
        ], 429, ['Retry-After' => (string) $retryAfter]);
    }
}
