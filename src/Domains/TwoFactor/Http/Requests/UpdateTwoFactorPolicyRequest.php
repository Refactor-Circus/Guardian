<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\UpdateTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactorPolicies;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\UpdateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class UpdateTwoFactorPolicyRequest extends Request
{
    use NamesTwoFactorPolicies;

    public function rules(): array
    {
        return UpdateTwoFactorPolicyData::rules();
    }

    public function persist(): JsonResponse
    {
        return (new TwoFactorPolicyResource(app(UpdateTwoFactorPolicyAction::class)->execute($this->policy(), UpdateTwoFactorPolicyData::fromValidated($this->validated()))))->response();
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
