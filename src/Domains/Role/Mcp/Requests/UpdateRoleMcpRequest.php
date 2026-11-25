<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Role\Actions\UpdateRoleAction;
use RefactorCircus\Guardian\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;
use RefactorCircus\Guardian\Support\Scopes;

final class UpdateRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    protected function scope(): ?Model
    {
        return $this->roleOwner();
    }

    protected function rules(): array
    {
        return $this->roleRules() + UpdateRoleData::rules() + Scopes::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new RoleResource(app(UpdateRoleAction::class)->execute($this->role(), UpdateRoleData::fromValidated($validated), $this->actor())))->toStructuredResponse();
    }
}
