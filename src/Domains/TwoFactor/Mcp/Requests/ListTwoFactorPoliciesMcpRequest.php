<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ListTwoFactorPoliciesAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactorPolicies;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ListTwoFactorPoliciesData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ListTwoFactorPoliciesMcpRequest extends Request
{
    use NamesTwoFactorPolicies;

    protected function rules(): array
    {
        return ListTwoFactorPoliciesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return TwoFactorPolicyResource::collection(app(ListTwoFactorPoliciesAction::class)->execute(ListTwoFactorPoliciesData::fromValidated($validated)))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
