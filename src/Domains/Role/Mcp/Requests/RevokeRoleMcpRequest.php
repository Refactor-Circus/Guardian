<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use RefactorCircus\Guardian\Domains\Role\Actions\RevokeRoleAction;

final class RevokeRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.assign';
    }

    protected function scope(): ?Model
    {
        return $this->assignmentScope();
    }

    protected function rules(): array
    {
        return ['assignment' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(RevokeRoleAction::class)->execute($this->namedAssignment(), $this->actor());

        return Response::text('Role revoked.');
    }
}
