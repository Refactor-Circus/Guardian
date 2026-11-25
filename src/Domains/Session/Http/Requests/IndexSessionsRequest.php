<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Actions\ListSessionsAction;
use RefactorCircus\Guardian\Domains\Session\Data\ListSessionsData;
use RefactorCircus\Guardian\Domains\Session\Resources\SessionResource;

final class IndexSessionsRequest extends OwnSessionRequest
{
    public function rules(): array
    {
        return ListSessionsData::rules();
    }

    public function persist(): JsonResponse
    {
        return SessionResource::collection(app(ListSessionsAction::class)->execute($this->signedIn(), ListSessionsData::fromValidated($this->validated())))->response();
    }
}
