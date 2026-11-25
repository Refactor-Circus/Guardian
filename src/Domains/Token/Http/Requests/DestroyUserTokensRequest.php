<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Token\Actions\RevokeUserTokensAction;
use RefactorCircus\Guardian\Domains\Token\Concerns\NamesTokens;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * Revoke every API token of another user: `guardian.tokens.manage`.
 */
final class DestroyUserTokensRequest extends Request
{
    use NamesTokens;

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return new JsonResponse(['revoked' => app(RevokeUserTokensAction::class)->execute($this->namedUser(), $this->actingUser())]);
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
