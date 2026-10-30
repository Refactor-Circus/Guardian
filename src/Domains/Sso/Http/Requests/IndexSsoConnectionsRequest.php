<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Sso\Actions\ListSsoConnectionsAction;
use RefactorCircus\Guardian\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource;

final class IndexSsoConnectionsRequest extends SsoOrganizationRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.view';
    }

    public function rules(): array
    {
        return ListSsoConnectionsData::rules();
    }

    public function persist(): JsonResponse
    {
        return SsoConnectionResource::collection(app(ListSsoConnectionsAction::class)->execute(ListSsoConnectionsData::fromValidated(['organization' => $this->organization()->slug] + $this->validated())))->response();
    }
}
