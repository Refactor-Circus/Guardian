<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\ShowTwoFactorAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Resources\TwoFactorStatusResource;

final class ShowTwoFactorRequest extends OwnTwoFactorRequest
{
    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return (new TwoFactorStatusResource(app(ShowTwoFactorAction::class)->execute($this->signedIn())))->response();
    }
}
