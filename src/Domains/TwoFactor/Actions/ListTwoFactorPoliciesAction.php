<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ListTwoFactorPoliciesData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPoliciesListedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPoliciesListingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Keystone\Actions\Action;

final class ListTwoFactorPoliciesAction extends Action
{
    /**
     * The two-factor policies, oldest first.
     *
     * @return LengthAwarePaginator<int, TwoFactorPolicyModel>
     */
    protected function handle(ListTwoFactorPoliciesData $filters = new ListTwoFactorPoliciesData): LengthAwarePaginator
    {
        TwoFactorPoliciesListingActionEvent::dispatch($filters);

        $policies = TwoFactorPolicyModel::query()->oldest()->oldest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        TwoFactorPoliciesListedActionEvent::dispatch($filters);

        return $policies;
    }
}
