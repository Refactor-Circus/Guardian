<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Actions\RevokeSessionAction;

final class DestroySessionRequest extends OwnSessionRequest
{
    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        app(RevokeSessionAction::class)->execute($this->ownSession(), $this->signedIn());

        return new JsonResponse(null, 204);
    }
}
