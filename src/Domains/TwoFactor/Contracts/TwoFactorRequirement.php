<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;

/**
 * Whether a user must use two-factor, with which methods, and by when
 * (criterion 34). Bound to `PolicyTwoFactorRequirement` by default; a host
 * binds its own to decide another way.
 */
interface TwoFactorRequirement
{
    public function for(Authenticatable $user): Requirement;
}
