<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Actions\ListRoleAssignmentsAction;
use RefactorCircus\Guardian\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleAssignmentResource;

final class IndexRoleAssignmentsRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    protected function scope(): ?Model
    {
        return $this->filterScope();
    }

    public function rules(): array
    {
        return ListRoleAssignmentsData::rules();
    }

    public function persist(): JsonResponse
    {
        return RoleAssignmentResource::collection(app(ListRoleAssignmentsAction::class)->execute(ListRoleAssignmentsData::fromValidated($this->validated())))->response();
    }
}
