<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Sso\Actions\ListSsoIdentitiesAction;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoIdentityResource;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\SsoUserRequest;

final class IndexUserSsoIdentitiesRequest extends SsoUserRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return ListSsoIdentitiesData::rules();
    }

    public function persist(): JsonResponse
    {
        return SsoIdentityResource::collection(app(ListSsoIdentitiesAction::class)->execute(ListSsoIdentitiesData::fromValidated(['user' => $this->targetUser()->getRouteKey()] + $this->validated())))->response();
    }
}
