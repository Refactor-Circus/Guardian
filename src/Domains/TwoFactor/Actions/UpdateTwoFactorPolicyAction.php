<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\UpdateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyUpdatedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyUpdatingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Keystone\Actions\Action;
use RefactorCircus\Keystone\Data\Missing;

final class UpdateTwoFactorPolicyAction extends Action
{
    /**
     * Change whether a policy requires two-factor, its methods or its grace.
     */
    protected function handle(TwoFactorPolicyModel $policy, UpdateTwoFactorPolicyData $data): TwoFactorPolicyModel
    {
        TwoFactorPolicyUpdatingActionEvent::dispatch($policy, $data);

        $changes = array_filter([
            'required' => $data->required,
            'methods' => $data->methods,
            'grace_days' => $data->graceDays,
        ], fn (mixed $value): bool => ! $value instanceof Missing);

        DB::transaction(fn (): bool => $policy->update($changes));

        TwoFactorPolicyUpdatedActionEvent::dispatch($policy);

        return $policy;
    }
}
