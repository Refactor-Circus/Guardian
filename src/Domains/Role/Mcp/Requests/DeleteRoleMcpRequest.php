<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\Role\Actions\DeleteRoleAction;
use RefactorCircus\Guardian\Support\Scopes;

final class DeleteRoleMcpRequest extends RoleMcpRequest
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
        return $this->roleRules() + Scopes::rules();
    }

    protected function handle(array $validated): Response
    {
        app(DeleteRoleAction::class)->execute($this->role(), $this->actor());

        return Response::text('Role deleted.');
    }
}
