<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Guardian\Domains\Role\Actions\ShowRoleAction;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;
use RefactorCircus\Guardian\Support\Scopes;

final class ShowRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope() ?? $this->roleOwner();
    }

    protected function rules(): array
    {
        return $this->roleRules() + Scopes::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new RoleResource(app(ShowRoleAction::class)->execute($this->role())))->toStructuredResponse();
    }
}
