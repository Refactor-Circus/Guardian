<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Guardian\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleAssignmentResource;

final class AssignRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.assign';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope();
    }

    protected function rules(): array
    {
        return AssignRoleData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new RoleAssignmentResource(app(AssignRoleAction::class)->execute(AssignRoleData::fromValidated($validated), $this->actor())))->toStructuredResponse();
    }
}
