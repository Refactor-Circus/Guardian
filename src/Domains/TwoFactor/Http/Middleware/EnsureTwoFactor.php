<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\TwoFactorSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * The `guardian.two-factor` middleware (criterion 33), for authenticated
 * routes: a user who holds a factor must have passed the challenge in this
 * session (403 `two_factor_challenge_required`), and a user who must use
 * two-factor and holds none of the allowed methods is refused once the
 * deadline has passed (403 `two_factor_enrollment_required`), and warned
 * before it with a `Guardian-Two-Factor-Deadline` header. A request without a
 * session passes the challenge check (D-012). Guardian's own two-factor
 * routes are never blocked.
 */
final class EnsureTwoFactor
{
    public const string DEADLINE_HEADER = 'Guardian-Two-Factor-Deadline';

    public function __construct(
        private readonly TwoFactorSession $session,
        private readonly TwoFactorRequirement $requirement,
        private readonly Factors $factors,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Authenticatable || $this->ownRoute($request)) {
            return $next($request);
        }

        $this->session->ensureNoChallengeOwed($request->hasSession() ? $request->session() : null, $user);

        $requirement = $this->requirement->for($user);

        if (! $requirement->required || array_intersect($this->factors->confirmedTypes($user), $requirement->methods) !== []) {
            return $next($request);
        }

        if ($requirement->due()) {
            throw new TwoFactorException(__('guardian::guardian.two_factor_enrollment_required'), 'two_factor_enrollment_required', 403, ['methods' => array_values(array_intersect($requirement->methods, $this->factors->available()))]);
        }

        $response = $next($request);
        $response->headers->set(self::DEADLINE_HEADER, (string) $requirement->deadline?->toIso8601String());

        return $response;
    }

    /**
     * Guardian's enrolment, challenge and status routes.
     */
    private function ownRoute(Request $request): bool
    {
        $name = $request->route()?->getName();

        return is_string($name) && str_starts_with($name, 'guardian.two-factor.');
    }
}
