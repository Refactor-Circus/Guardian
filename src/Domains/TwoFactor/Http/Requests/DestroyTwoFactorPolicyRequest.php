<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\DeleteTwoFactorPolicyAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactorPolicies;
use RefactorCircus\Guardian\Http\Requests\Request;

final class DestroyTwoFactorPolicyRequest extends Request
{
    use NamesTwoFactorPolicies;

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        app(DeleteTwoFactorPolicyAction::class)->execute($this->policy());

        return new JsonResponse(null, 204);
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
