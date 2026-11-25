<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\TwoFactorSession;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * A request about the signed-in user's own two-factor: any signed-in user
 * may make it (a guest gets a 401, whatever `guardian.authorization` says).
 */
abstract class OwnTwoFactorRequest extends Request
{
    use NamesTwoFactor;

    public function authorize(): bool
    {
        return $this->actor() !== null && parent::authorize();
    }

    protected function ability(): string
    {
        return 'guardian.two-factor.manage';
    }

    protected function self(): ?Model
    {
        return $this->actor();
    }

    /**
     * Changing two-factor needs a session that owes no challenge: someone
     * holding only the password must not add, remove or replace a factor
     * (D-041). A request without a session is not held back (D-012).
     */
    protected function ensureNoChallengeOwed(): void
    {
        app(TwoFactorSession::class)->ensureNoChallengeOwed($this->hasSession() ? $this->session() : null, $this->signedIn());
    }

    /**
     * A factor was just enrolled: a session that owed no challenge counts
     * as confirmed from now on.
     */
    protected function enrolled(): void
    {
        if ($this->hasSession()) {
            app(TwoFactorSession::class)->enrolled($this->session(), $this->signedIn());
        }
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
