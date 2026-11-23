<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Actions\ListUserSessionsAction;
use RefactorCircus\Guardian\Domains\Session\Concerns\NamesSessions;
use RefactorCircus\Guardian\Domains\Session\Data\ListSessionsData;
use RefactorCircus\Guardian\Domains\Session\Resources\SessionResource;
use RefactorCircus\Guardian\Http\Requests\Request;

/**
 * Another user's sessions: `guardian.sessions.manage`.
 */
final class IndexUserSessionsRequest extends Request
{
    use NamesSessions;

    public function rules(): array
    {
        return ListSessionsData::rules();
    }

    public function persist(): JsonResponse
    {
        return SessionResource::collection(app(ListUserSessionsAction::class)->execute($this->namedUser(), ListSessionsData::fromValidated($this->validated())))->response();
    }

    protected function argument(string $key): mixed
    {
        return $this->route($key) ?? $this->input($key);
    }
}
