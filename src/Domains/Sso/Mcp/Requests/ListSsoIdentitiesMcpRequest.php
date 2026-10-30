<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Sso\Actions\ListSsoIdentitiesAction;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoIdentityResource;

final class ListSsoIdentitiesMcpRequest extends SsoUserMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return $this->userRules() + ListSsoIdentitiesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $identities = app(ListSsoIdentitiesAction::class)->execute(ListSsoIdentitiesData::fromValidated(['user' => $this->targetUser()->getRouteKey()] + $validated));

        return SsoIdentityResource::collection($identities)->toStructuredResponse();
    }
}
