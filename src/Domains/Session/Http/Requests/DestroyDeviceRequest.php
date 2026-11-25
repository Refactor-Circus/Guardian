<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Session\Actions\ForgetDeviceAction;

final class DestroyDeviceRequest extends OwnSessionRequest
{
    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        app(ForgetDeviceAction::class)->execute($this->ownDevice(), $this->signedIn());

        return new JsonResponse(null, 204);
    }
}
