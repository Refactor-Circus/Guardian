<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use RefactorCircus\Guardian\Domains\Role\Actions\DeleteRoleAction;
use RefactorCircus\Guardian\Support\Scopes;

final class DestroyRoleRequest extends RoleRequest
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
        return Scopes::rules();
    }

    public function persist(): Response
    {
        app(DeleteRoleAction::class)->execute($this->role(), $this->actor());

        return response()->noContent();
    }
}
