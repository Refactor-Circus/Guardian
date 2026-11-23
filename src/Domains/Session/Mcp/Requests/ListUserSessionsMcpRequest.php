<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Session\Actions\ListUserSessionsAction;
use RefactorCircus\Guardian\Domains\Session\Concerns\NamesSessions;
use RefactorCircus\Guardian\Domains\Session\Data\ListSessionsData;
use RefactorCircus\Guardian\Domains\Session\Resources\SessionResource;
use RefactorCircus\Guardian\Mcp\Requests\Request;

final class ListUserSessionsMcpRequest extends Request
{
    use NamesSessions;

    protected function rules(): array
    {
        return ['user' => ['required', 'regex:/^[\w\-]{1,64}$/']] + ListSessionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        unset($validated['user']);

        return SessionResource::collection(app(ListUserSessionsAction::class)->execute($this->namedUser(), ListSessionsData::fromValidated($validated)))->toStructuredResponse();
    }

    protected function argument(string $key): mixed
    {
        return $this->get($key);
    }
}
