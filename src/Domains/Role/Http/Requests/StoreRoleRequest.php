<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Guardian\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;

final class StoreRoleRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope();
    }

    public function rules(): array
    {
        return CreateRoleData::rules();
    }

    public function persist(): JsonResponse
    {
        $role = app(CreateRoleAction::class)->execute(CreateRoleData::fromValidated($this->validated()), $this->actor());

        return (new RoleResource($role))->response()->setStatusCode(201);
    }
}
