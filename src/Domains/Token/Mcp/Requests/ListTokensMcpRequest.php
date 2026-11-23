<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Token\Actions\ListTokensAction;
use RefactorCircus\Guardian\Domains\Token\Concerns\NamesTokens;
use RefactorCircus\Guardian\Domains\Token\Resources\TokenResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ListTokensMcpRequest extends Request
{
    use NamesTokens;

    protected function authorize(): bool
    {
        return $this->actor() !== null && parent::authorize();
    }

    protected function self(): ?Model
    {
        return $this->actor();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return TokenResource::collection(app(ListTokensAction::class)->execute($this->signedIn()))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
