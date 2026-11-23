<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ListPasskeysAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\PasskeyResource;

final class IndexPasskeysRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return PasskeyResource::collection(app(ListPasskeysAction::class)->execute($this->signedIn()))->response();
    }
}
