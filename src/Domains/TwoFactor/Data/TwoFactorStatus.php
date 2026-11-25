<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;

/**
 * A user's two-factor status (criterion 31).
 */
final readonly class TwoFactorStatus
{
    /**
     * @param  Collection<int, TwoFactorMethodModel>  $methods
     * @param  Collection<int, PasskeyModel>  $passkeys
     * @param  list<string>  $allowedMethods
     */
    public function __construct(
        public bool $enabled,
        public Collection $methods,
        public Collection $passkeys,
        public bool $passkeysAvailable,
        public int $recoveryCodesRemaining,
        public Requirement $requirement,
        public array $allowedMethods,
    ) {}
}
