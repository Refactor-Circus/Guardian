<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Role\Actions\ListRoleAssignmentsAction;
use RefactorCircus\Guardian\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleAssignmentResource;

final class ListRoleAssignmentsMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    protected function scope(): ?Model
    {
        return $this->filterScope();
    }

    protected function rules(): array
    {
        return ListRoleAssignmentsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return RoleAssignmentResource::collection(app(ListRoleAssignmentsAction::class)->execute(ListRoleAssignmentsData::fromValidated($validated)))->toStructuredResponse();
    }
}
