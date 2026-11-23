<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ResetTwoFactorAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactor;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * An admin's reset of another user's two-factor: `guardian.two-factor.manage`.
 */
final class DestroyUserTwoFactorRequest extends Request
{
    use NamesTwoFactor;

    protected function ability(): string
    {
        return 'guardian.two-factor.manage';
    }

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        app(ResetTwoFactorAction::class)->execute($this->namedUser(), $this->actor());

        return new JsonResponse(null, 204);
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
