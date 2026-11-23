<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Actions\ListUserAbilitiesAction;
use RefactorCircus\Guardian\Domains\Role\Data\ListUserAbilitiesData;
use RefactorCircus\Guardian\Domains\Role\Resources\UserAbilitiesResource;

final class IndexUserAbilitiesRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope();
    }

    /**
     * Everyone may list their own abilities.
     */
    protected function self(): Model
    {
        return $this->namedUser();
    }

    public function rules(): array
    {
        return ListUserAbilitiesData::rules();
    }

    public function persist(): JsonResponse
    {
        return (new UserAbilitiesResource(app(ListUserAbilitiesAction::class)->execute($this->namedUser(), ListUserAbilitiesData::fromValidated($this->validated()))))->response();
    }
}
