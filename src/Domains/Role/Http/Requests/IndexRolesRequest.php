<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Domains\Role\Actions\ListRolesAction;
use RefactorCircus\Guardian\Domains\Role\Data\ListRolesData;
use RefactorCircus\Guardian\Domains\Role\Resources\RoleResource;

final class IndexRolesRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.view';
    }

    protected function scope(): ?Model
    {
        return $this->namedScope();
    }

    public function rules(): array
    {
        return ListRolesData::rules();
    }

    public function persist(): JsonResponse
    {
        return RoleResource::collection(app(ListRolesAction::class)->execute(ListRolesData::fromValidated($this->validated())))->response();
    }
}
