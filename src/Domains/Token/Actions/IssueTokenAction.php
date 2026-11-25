<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\Token\Data\IssuedToken;
use RefactorCircus\Guardian\Domains\Token\Data\IssueTokenData;
use RefactorCircus\Guardian\Domains\Token\Events\TokenIssuedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\TokenIssuingActionEvent;
use RefactorCircus\Guardian\Domains\Token\Services\TokenLog;
use RefactorCircus\Guardian\Domains\Token\TokenManager;
use RefactorCircus\Keystone\Actions\Action;

final class IssueTokenAction extends Action
{
    public function __construct(
        private readonly TokenManager $tokens,
        private readonly TokenLog $log,
    ) {}

    /**
     * Issue an API token to the signed-in user (criterion 48) through the
     * token driver. Abilities stay within `guardian.tokens.abilities` (all
     * of them when none are asked for) and the expiry within
     * `guardian.tokens.max_lifetime_days` (that limit when none is asked
     * for). The plain-text value is answered once; the events and
     * `token.issued` carry only the token's id and name.
     */
    protected function handle(Authenticatable $user, IssueTokenData $data): IssuedToken
    {
        TokenIssuingActionEvent::dispatch($user, $data);

        $driver = $this->tokens->driver();
        $allowed = IssueTokenData::allowedAbilities();
        $latest = IssueTokenData::latestExpiry();
        $abilities = $data->abilities ?? $allowed;
        $expiresAt = $data->expiresAt?->toImmutable() ?? $latest;

        if (array_diff($abilities, $allowed) !== []) {
            throw ValidationException::withMessages(['abilities' => __('validation.in', ['attribute' => 'abilities'])]);
        }

        if ($expiresAt !== null && ($expiresAt->isPast() || ($latest !== null && $expiresAt->greaterThan($latest)))) {
            throw ValidationException::withMessages(['expires_at' => __('validation.before_or_equal', ['attribute' => 'expires at', 'date' => $latest?->toIso8601String() ?? 'now'])]);
        }

        $issued = DB::transaction(function () use ($driver, $user, $data, $abilities, $expiresAt): IssuedToken {
            $issued = $driver->issue($user, $data->name, $abilities, $expiresAt);

            $this->log->record(SecurityEventType::TokenIssued, $user, null, ['id' => $issued->token->id, 'name' => $issued->token->name]);

            return $issued;
        });

        TokenIssuedActionEvent::dispatch($user, $issued->token);

        return $issued;
    }
}
