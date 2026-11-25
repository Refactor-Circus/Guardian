<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Guardian\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;

final class CreateRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope();
    }

    protected function rules(): array
    {
        return CreateRoleData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new RoleResource(app(CreateRoleAction::class)->execute(CreateRoleData::fromValidated($validated), $this->actor())))->toStructuredResponse();
    }
}
