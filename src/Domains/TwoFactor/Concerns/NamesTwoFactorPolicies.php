<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Concerns;

use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;

/**
 * What a request about the enforcement policies names: a policy by id.
 */
trait NamesTwoFactorPolicies
{
    /**
     * An argument the caller sent.
     */
    abstract protected function argument(string $key): mixed;

    protected function ability(): string
    {
        return 'guardian.two-factor.manage';
    }

    protected function policy(): TwoFactorPolicyModel
    {
        $id = $this->argument('policy');

        return TwoFactorPolicyModel::query()->whereKey(is_scalar($id) ? (string) $id : '')->firstOrFail();
    }
}
