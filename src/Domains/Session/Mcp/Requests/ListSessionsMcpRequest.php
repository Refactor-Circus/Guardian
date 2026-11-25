<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Session\Actions\ListSessionsAction;
use RefactorCircus\Guardian\Domains\Session\Data\ListSessionsData;
use RefactorCircus\Guardian\Domains\Session\Resources\SessionResource;

final class ListSessionsMcpRequest extends OwnSessionMcpRequest
{
    protected function rules(): array
    {
        return ListSessionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return SessionResource::collection(app(ListSessionsAction::class)->execute($this->signedIn(), ListSessionsData::fromValidated($validated)))->toStructuredResponse();
    }
}
