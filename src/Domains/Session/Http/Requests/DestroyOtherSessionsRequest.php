<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Actions\RevokeOtherSessionsAction;
use RefactorCircus\Guardian\Domains\Session\Data\RevokeOtherSessionsData;
use RefactorCircus\Guardian\Domains\Session\Services\Sessions;

/**
 * Sign out everywhere else: every other session (and, with `tokens`, the
 * API tokens) of the caller.
 */
final class DestroyOtherSessionsRequest extends OwnSessionRequest
{
    public function rules(): array
    {
        return RevokeOtherSessionsData::rules();
    }

    public function persist(): JsonResponse
    {
        $result = app(RevokeOtherSessionsAction::class)->execute($this->signedIn(), RevokeOtherSessionsData::fromValidated($this->validated()), app(Sessions::class)->currentHash($this));

        return new JsonResponse(['revoked' => $result->sessions, 'tokens_revoked' => $result->tokens]);
    }
}
