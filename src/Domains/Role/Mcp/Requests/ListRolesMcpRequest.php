<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Role\Actions\ListRolesAction;
use RefactorCircus\Guardian\Domains\Role\Data\ListRolesData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;

final class ListRolesMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope();
    }

    protected function rules(): array
    {
        return ListRolesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return RoleResource::collection(app(ListRolesAction::class)->execute(ListRolesData::fromValidated($validated)))->toStructuredResponse();
    }
}
