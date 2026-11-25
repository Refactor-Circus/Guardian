<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\CreateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyCreatedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorPolicyCreatingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorPolicyModel;
use RefactorCircus\Guardian\Support\Scopes;
use RefactorCircus\Keystone\Actions\Action;

final class CreateTwoFactorPolicyAction extends Action
{
    public function __construct(private readonly Scopes $scopes) {}

    /**
     * Make a two-factor policy (criterion 34). Its scope is stored as the
     * scope model's morph type and key.
     */
    protected function handle(CreateTwoFactorPolicyData $data): TwoFactorPolicyModel
    {
        TwoFactorPolicyCreatingActionEvent::dispatch($data);

        $scope = $this->scopes->resolve($data->scopeType, $data->scopeId);

        $policy = DB::transaction(fn (): TwoFactorPolicyModel => TwoFactorPolicyModel::query()->create([
            'scope_type' => $scope?->getMorphClass(),
            'scope_id' => Scopes::key($scope),
            'role' => $data->role,
            'required' => $data->required,
            'methods' => $data->methods,
            'grace_days' => $data->graceDays,
        ]));

        TwoFactorPolicyCreatedActionEvent::dispatch($policy);

        return $policy;
    }
}
