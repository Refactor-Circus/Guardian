<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Token\Actions\ListTokensAction;
use RefactorCircus\Guardian\Domains\Token\Resources\TokenResource;

final class IndexTokensRequest extends OwnTokenRequest
{
    public function rules(): array
    {
        return [];
    }

    public function persist(): JsonResponse
    {
        return TokenResource::collection(app(ListTokensAction::class)->execute($this->signedIn()))->response();
    }
}
