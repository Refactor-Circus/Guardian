<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Token\Actions\RevokeTokenAction;

final class DestroyTokenRequest extends OwnTokenRequest
{
    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        app(RevokeTokenAction::class)->execute($this->signedIn(), $this->tokenId(), $this->signedIn());

        return new JsonResponse(null, 204);
    }
}
