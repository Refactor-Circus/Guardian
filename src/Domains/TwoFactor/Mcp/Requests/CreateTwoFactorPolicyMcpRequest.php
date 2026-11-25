<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\CreateTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactorPolicies;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\CreateTwoFactorPolicyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorPolicyResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class CreateTwoFactorPolicyMcpRequest extends Request
{
    use NamesTwoFactorPolicies;

    protected function rules(): array
    {
        return CreateTwoFactorPolicyData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new TwoFactorPolicyResource(app(CreateTwoFactorPolicyAction::class)->execute(CreateTwoFactorPolicyData::fromValidated($validated))))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
