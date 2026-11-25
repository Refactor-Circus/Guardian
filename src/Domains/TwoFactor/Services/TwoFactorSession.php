<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * The two-factor state of a signed-in session (criterion 32, D-012): a
 * sign-in by a user with a confirmed factor leaves the session pending until
 * a challenge passes, which confirms it and regenerates its id. Both marks
 * name the user they belong to, so they never carry over to another.
 */
final class TwoFactorSession
{
    public const string PENDING = 'guardian.two_factor.pending';

    public const string CONFIRMED = 'guardian.two_factor.confirmed';

    public function __construct(
        private readonly Factors $factors,
        private readonly RecoveryCodes $recoveryCodes,
    ) {}

    /**
     * A new sign-in: nothing confirmed yet, and pending when the user holds
     * a factor.
     */
    public function signedIn(Session $session, Authenticatable $user): void
    {
        $session->forget([self::PENDING, self::CONFIRMED]);

        if ($this->factors->has($user)) {
            $session->put(self::PENDING, (string) UserKeys::of($user));
        }
    }

    public function confirmed(Session $session, Authenticatable $user): bool
    {
        return $session->get(self::CONFIRMED) === (string) UserKeys::of($user);
    }

    /**
     * A challenge passed: the session is confirmed for the user, under a new
     * id.
     */
    public function confirm(Session $session, Authenticatable $user): void
    {
        $session->forget(self::PENDING);
        $session->put(self::CONFIRMED, (string) UserKeys::of($user));
        $session->regenerate();
    }

    /**
     * A first factor was just enrolled in a session that owed no challenge:
     * the user proved it, so the session counts as confirmed.
     */
    public function enrolled(Session $session, Authenticatable $user): void
    {
        if ($session->get(self::PENDING) === null && ! $this->confirmed($session, $user)) {
            $session->put(self::CONFIRMED, (string) UserKeys::of($user));
        }
    }

    /**
     * Whether the user must pass a challenge in this session first: they hold
     * a factor and the session is not confirmed for them. A request without a
     * session never does (D-012).
     */
    public function owesChallenge(?Session $session, Authenticatable $user): bool
    {
        return $session !== null && ! $this->confirmed($session, $user) && $this->factors->has($user);
    }

    /**
     * @throws TwoFactorException 403 `two_factor_challenge_required`, with the methods that may answer it
     */
    public function ensureNoChallengeOwed(?Session $session, Authenticatable $user): void
    {
        if ($this->owesChallenge($session, $user)) {
            throw new TwoFactorException(__('guardian::guardian.two_factor_challenge_required'), 'two_factor_challenge_required', 403, ['methods' => $this->challengeMethods($user)]);
        }
    }

    /**
     * What the user may answer a challenge with: their confirmed factors'
     * types, and `recovery` while they have codes left.
     *
     * @return list<string>
     */
    public function challengeMethods(Authenticatable $user): array
    {
        $methods = $this->factors->confirmedTypes($user);

        if ($this->recoveryCodes->remaining(UserKeys::of($user)) > 0) {
            $methods[] = 'recovery';
        }

        return $methods;
    }
}
