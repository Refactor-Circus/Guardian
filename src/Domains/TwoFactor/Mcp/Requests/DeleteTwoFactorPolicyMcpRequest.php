<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\DeleteTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactorPolicies;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class DeleteTwoFactorPolicyMcpRequest extends Request
{
    use NamesTwoFactorPolicies;

    protected function rules(): array
    {
        return ['policy' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(DeleteTwoFactorPolicyAction::class)->execute($this->policy());

        return Response::text('Two-factor policy deleted.');
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
