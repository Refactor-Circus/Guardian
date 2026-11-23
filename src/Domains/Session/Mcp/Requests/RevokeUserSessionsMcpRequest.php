<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Session\Actions\RevokeUserSessionsAction;
use RefactorCircus\Guardian\Domains\Session\Concerns\NamesSessions;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class RevokeUserSessionsMcpRequest extends Request
{
    use NamesSessions;

    protected function rules(): array
    {
        return ['user' => ['required', 'regex:/^[\w\-]{1,64}$/']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $actor = $this->actor();
        $result = app(RevokeUserSessionsAction::class)->execute($this->namedUser(), $actor instanceof Authenticatable ? $actor : null);

        return Response::structured(['revoked' => $result->sessions]);
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
