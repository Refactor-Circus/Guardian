<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Role\Actions\ListUserAbilitiesAction;
use RefactorCircus\Guardian\Domains\Role\Data\ListUserAbilitiesData;
use RefactorCircus\Guardian\Domains\Role\Resources\UserAbilitiesResource;

final class ListUserAbilitiesMcpRequest extends RoleMcpRequest
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

    protected function rules(): array
    {
        return ['user' => ['required']] + ListUserAbilitiesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserAbilitiesResource(app(ListUserAbilitiesAction::class)->execute($this->namedUser(), ListUserAbilitiesData::fromValidated($validated))))->toStructuredResponse();
    }
}
