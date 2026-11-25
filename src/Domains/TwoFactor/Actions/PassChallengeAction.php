<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\PassChallengeData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\ChallengePassedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\ChallengePassingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class PassChallengeAction extends Action
{
    public const int MAX_FAILURES = 5;

    public function __construct(
        private readonly Factors $factors,
        private readonly SecurityLog $log,
    ) {}

    /**
     * Answer the two-factor challenge (criterion 32). Five failures a minute
     * per user, then 429. Every attempt is recorded: passed, failed, and a
     * recovery code used. The caller confirms the session.
     *
     * @throws TwoFactorException 429 once failures are used up
     * @throws ValidationException for a wrong answer
     */
    protected function handle(Model&Authenticatable $user, PassChallengeData $data): void
    {
        ChallengePassingActionEvent::dispatch($user, $data->method);

        $limiter = 'guardian-two-factor-challenge:'.UserKeys::of($user);

        if (RateLimiter::tooManyAttempts($limiter, self::MAX_FAILURES)) {
            throw TwoFactorException::throttled(max(RateLimiter::availableIn($limiter), 1));
        }

        // Outside a transaction: a wrong code's spent attempt must stay spent.
        if (! $this->factors->verifyAs($user, $data->method, $data->code, $data->assertion)) {
            RateLimiter::hit($limiter, 60);
            $this->log->record(SecurityEventType::TwoFactorChallengeFailed, $user, ['method' => $data->method]);

            throw ValidationException::withMessages([$data->method === 'passkey' ? 'assertion' : 'code' => __('guardian::guardian.two_factor_invalid_code')]);
        }

        DB::transaction(function () use ($user, $data): void {
            if ($data->method === 'recovery') {
                $this->log->record(SecurityEventType::TwoFactorRecoveryCodeUsed, $user);
            }

            $this->log->record(SecurityEventType::TwoFactorChallengePassed, $user, ['method' => $data->method]);
        });

        ChallengePassedActionEvent::dispatch($user, $data->method);
    }
}
