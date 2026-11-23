<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Token\Actions\RevokeUserTokensAction;
use RefactorCircus\Guardian\Domains\Token\Concerns\NamesTokens;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class RevokeUserTokensMcpRequest extends Request
{
    use NamesTokens;

    protected function rules(): array
    {
        return ['user' => ['required', 'regex:/^[\w\-]{1,64}$/']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['revoked' => app(RevokeUserTokensAction::class)->execute($this->namedUser(), $this->actingUser())]);
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
