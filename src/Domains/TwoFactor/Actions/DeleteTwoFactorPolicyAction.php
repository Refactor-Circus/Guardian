<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyDeletedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyDeletingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Keystone\Actions\Action;

final class DeleteTwoFactorPolicyAction extends Action
{
    protected function handle(TwoFactorPolicyModel $policy): void
    {
        TwoFactorPolicyDeletingActionEvent::dispatch($policy);

        DB::transaction(fn (): ?bool => $policy->delete());

        TwoFactorPolicyDeletedActionEvent::dispatch($policy);
    }
}
