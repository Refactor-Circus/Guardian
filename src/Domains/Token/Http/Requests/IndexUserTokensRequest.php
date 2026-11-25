<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Token\Actions\ListUserTokensAction;
use RefactorCircus\Guardian\Domains\Token\Concerns\NamesTokens;
use RefactorCircus\Guardian\Domains\Token\Resources\TokenResource;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * Another user's API tokens: `guardian.tokens.manage`.
 */
final class IndexUserTokensRequest extends Request
{
    use NamesTokens;

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return TokenResource::collection(app(ListUserTokensAction::class)->execute($this->namedUser()))->response();
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
