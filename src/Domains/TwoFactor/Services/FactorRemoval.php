<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;

/**
 * The guard on removing a confirmed factor (D-023): never the last one while
 * two-factor is required, and only with proof of another factor or a
 * recovery code, so a stolen session alone cannot strip two-factor.
 */
final class FactorRemoval
{
    public function __construct(
        private readonly Factors $factors,
        private readonly RecoveryCodes $recoveryCodes,
        private readonly TwoFactorRequirement $requirement,
        private readonly SecurityLog $log,
    ) {}

    /**
     * @throws TwoFactorException when it is the last factor and two-factor is required
     * @throws ValidationException when the code proves no other factor
     */
    public function authorize(Authenticatable $user, string $factorId, RemoveTwoFactorMethodData $data): void
    {
        if (! $this->factors->has($user, $factorId) && $this->requirement->for($user)->required) {
            throw TwoFactorException::required();
        }

        $proof = $this->factors->verify($user, $data->code, $factorId);

        if ($proof === null) {
            throw ValidationException::withMessages(['code' => __('guardian::guardian.two_factor_other_factor_needed')]);
        }

        if ($proof === 'recovery') {
            $this->log->record(SecurityEventType::TwoFactorRecoveryCodeUsed, $user);
        }
    }

    /**
     * Once the user holds no factor, their recovery codes go too.
     */
    public function afterRemoving(Authenticatable $user): void
    {
        if (! $this->factors->has($user)) {
            $this->recoveryCodes->forget(UserKeys::of($user));
        }
    }
}
