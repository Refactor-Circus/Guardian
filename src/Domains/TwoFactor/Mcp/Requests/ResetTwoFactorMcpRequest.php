<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ResetTwoFactorAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Concerns\NamesTwoFactor;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ResetTwoFactorMcpRequest extends Request
{
    use NamesTwoFactor;

    protected function ability(): string
    {
        return 'guardian.two-factor.manage';
    }

    protected function rules(): array
    {
        return [
            'user' => ['required', 'regex:/^[\w\-]{1,64}$/'],
        ];
    }

    protected function handle(array $validated): Response
    {
        app(ResetTwoFactorAction::class)->execute($this->namedUser(), $this->actor());

        return Response::text('Two-factor reset.');
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
