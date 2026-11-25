<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ListTwoFactorPoliciesAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactorPolicies;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ListTwoFactorPoliciesData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class IndexTwoFactorPoliciesRequest extends Request
{
    use NamesTwoFactorPolicies;

    public function rules(): array
    {
        return ListTwoFactorPoliciesData::rules();
    }

    public function persist(): JsonResponse
    {
        return TwoFactorPolicyResource::collection(app(ListTwoFactorPoliciesAction::class)->execute(ListTwoFactorPoliciesData::fromValidated($this->validated())))->response();
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
