<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\Password\Data\PasswordPolicySettings;
use RefactorCircus\Guardian\Domains\Password\Exceptions\PasswordException;
use RefactorCircus\Guardian\Domains\Password\Services\PasswordHistory;
use Symfony\Component\HttpFoundation\Response;

/**
 * The `guardian.password-fresh` middleware (criterion 42): with
 * `guardian.passwords.max_age_days` set, a signed-in user whose password
 * last changed longer ago than that gets a 403 `password_expired`. A user
 * with no history gets a baseline entry dated now, and is not refused.
 */
final class EnsurePasswordFresh
{
    public function __construct(private readonly PasswordHistory $history) {}

    public function handle(Request $request, Closure $next): Response
    {
        $days = PasswordPolicySettings::fromConfig()->maxAgeDays;
        $user = $request->user();

        if ($days === null || ! $user instanceof Authenticatable) {
            return $next($request);
        }

        $changed = ($this->history->latest($user) ?? $this->history->baseline($user))->created_at;

        if ($changed->lt(now()->subDays($days))) {
            return PasswordException::expired()->render();
        }

        return $next($request);
    }
}
