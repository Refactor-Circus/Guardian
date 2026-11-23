<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\CreateTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactorPolicies;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\CreateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Guardian\Http\Requests\Request;

final class StoreTwoFactorPolicyRequest extends Request
{
    use NamesTwoFactorPolicies;

    public function rules(): array
    {
        return CreateTwoFactorPolicyData::rules();
    }

    public function persist(): JsonResponse
    {
        $policy = app(CreateTwoFactorPolicyAction::class)->execute(CreateTwoFactorPolicyData::fromValidated($this->validated()));

        return (new TwoFactorPolicyResource($policy))->response()->setStatusCode(201);
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
