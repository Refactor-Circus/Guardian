<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use RefactorCircus\Guardian\Domains\Role\Actions\RevokeRoleAction;

final class DestroyRoleAssignmentRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.assign';
    }

    protected function scope(): ?Model
    {
        return $this->assignmentScope();
    }

    public function rules(): array
    {
        return [];
    }

    public function persist(): Response
    {
        app(RevokeRoleAction::class)->execute($this->namedAssignment(), $this->actor());

        return response()->noContent();
    }
}
