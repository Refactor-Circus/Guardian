<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Sso\Http\Requests\SsoOrganizationRequest;
use RefactorCircus\Guardian\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Guardian\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Guardian\Domains\Sso\Resources\SsoConnectionResource;

final class StoreSsoConnectionRequest extends SsoOrganizationRequest
{
    protected function ability(): string
    {
        return 'guardian.sso.manage';
    }

    public function rules(): array
    {
        return CreateSsoConnectionData::rules();
    }

    public function persist(): JsonResponse
    {
        $connection = app(CreateSsoConnectionAction::class)->execute($this->organization(), CreateSsoConnectionData::fromValidated($this->validated()));

        return (new SsoConnectionResource($connection))->response()->setStatusCode(201);
    }
}
