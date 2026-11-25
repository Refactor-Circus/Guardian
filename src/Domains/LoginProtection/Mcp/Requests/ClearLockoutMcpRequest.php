<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\LoginProtection\Actions\ClearLockoutAction;
use RefactorCircus\Guardian\Domains\LoginProtection\Concerns\NamesIpRules;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\ClearLockoutData;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ClearLockoutMcpRequest extends Request
{
    use NamesIpRules;

    protected function rules(): array
    {
        return ClearLockoutData::rules();
    }

    protected function handle(array $validated): Response
    {
        app(ClearLockoutAction::class)->execute(ClearLockoutData::fromValidated($validated), $this->actingUser());

        return Response::text('Lockout cleared.');
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
