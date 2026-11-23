<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\Requirement;

/**
 * Guardian's default `TwoFactorRequirement`: required for everyone when
 * `guardian.two_factor.required` is on, any method counts, due at once.
 */
final class PolicyTwoFactorRequirement implements TwoFactorRequirement
{
    public function __construct(private readonly Repository $config) {}

    public function for(Authenticatable $user): Requirement
    {
        return new Requirement(required: $this->config->get('guardian.two_factor.required') === true);
    }
}
