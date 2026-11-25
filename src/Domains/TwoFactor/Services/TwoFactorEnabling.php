<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * What confirming a factor does, whichever kind it is: mark it confirmed,
 * create recovery codes alongside the user's first factor, and record
 * `two_factor.enabled`. Call inside the confirming action's transaction.
 */
final class TwoFactorEnabling
{
    public function __construct(
        private readonly Factors $factors,
        private readonly RecoveryCodes $recoveryCodes,
        private readonly SecurityLog $log,
    ) {}

    /**
     * Confirm a factor; the recovery codes when it is the user's first.
     *
     * @return list<string>|null
     */
    public function confirm(Authenticatable $user, Model $factor): ?array
    {
        $first = ! $this->factors->has($user, $factor->getKey() === null ? null : (string) $factor->getKey());

        if ($factor instanceof TwoFactorMethodModel) {
            $factor->forceFill(['confirmed_at' => now(), 'last_used_at' => $factor->last_used_at ?? now()])->save();
        }

        $codes = $first ? $this->recoveryCodes->replace(UserKeys::of($user)) : null;

        $this->log->record(SecurityEventType::TwoFactorEnabled, $user, [
            'method' => $factor instanceof TwoFactorMethodModel ? $factor->type->value : 'passkey',
            'factor_id' => (string) $factor->getKey(),
        ]);

        return $codes;
    }
}
