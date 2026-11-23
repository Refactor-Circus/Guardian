<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Actions\UpdateRoleAction;
use RefactorCircus\Guardian\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;
use RefactorCircus\Guardian\Support\Scopes;

final class UpdateRoleRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    protected function scope(): ?Model
    {
        return $this->roleOwner();
    }

    public function rules(): array
    {
        return UpdateRoleData::rules() + Scopes::rules();
    }

    public function persist(): JsonResponse
    {
        $role = app(UpdateRoleAction::class)->execute($this->role(), UpdateRoleData::fromValidated($this->validated()), $this->actor());

        return (new RoleResource($role))->response();
    }
}
