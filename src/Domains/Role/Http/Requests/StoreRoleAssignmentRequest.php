<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Guardian\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleAssignmentResource;

final class StoreRoleAssignmentRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.assign';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope();
    }

    public function rules(): array
    {
        return AssignRoleData::rules();
    }

    public function persist(): JsonResponse
    {
        $assignment = app(AssignRoleAction::class)->execute(AssignRoleData::fromValidated($this->validated()), $this->actor());

        return (new RoleAssignmentResource($assignment))->response()->setStatusCode(201);
    }
}
