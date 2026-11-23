<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\UpdateTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactorPolicies;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\UpdateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class UpdateTwoFactorPolicyMcpRequest extends Request
{
    use NamesTwoFactorPolicies;

    protected function rules(): array
    {
        return ['policy' => ['required', 'string']] + UpdateTwoFactorPolicyData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['policy']);

        return (new TwoFactorPolicyResource(app(UpdateTwoFactorPolicyAction::class)->execute($this->policy(), UpdateTwoFactorPolicyData::fromValidated($validated))))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
