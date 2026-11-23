<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Actions\ShowRoleAction;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;
use RefactorCircus\Guardian\Support\Scopes;

final class ShowRoleRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope() ?? $this->roleOwner();
    }

    public function rules(): array
    {
        return Scopes::rules();
    }

    public function persist(): JsonResponse
    {
        return (new RoleResource(app(ShowRoleAction::class)->execute($this->role())))->response();
    }
}
