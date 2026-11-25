<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Actions\RevokeUserSessionsAction;
use RefactorCircus\Guardian\Domains\Session\Concerns\NamesSessions;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * Revoke every session of another user: `guardian.sessions.manage`.
 */
final class DestroyUserSessionsRequest extends Request
{
    use NamesSessions;

    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        $actor = $this->actor();
        $result = app(RevokeUserSessionsAction::class)->execute($this->namedUser(), $actor instanceof Authenticatable ? $actor : null);

        return new JsonResponse(['revoked' => $result->sessions]);
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
