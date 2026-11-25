<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Token\Actions\ListUserTokensAction;
use RefactorCircus\Guardian\Domains\Token\Concerns\NamesTokens;
use RefactorCircus\Guardian\Domains\Token\Resources\TokenResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ListUserTokensMcpRequest extends Request
{
    use NamesTokens;

    protected function rules(): array
    {
        return ['user' => ['required', 'regex:/^[\w\-]{1,64}$/']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return TokenResource::collection(app(ListUserTokensAction::class)->execute($this->namedUser()))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
