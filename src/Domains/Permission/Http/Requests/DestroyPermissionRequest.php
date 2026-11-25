<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Guardian\Domains\Permission\Actions\DeletePermissionAction;

final class DestroyPermissionRequest extends PermissionRequest
{
    protected function ability(): string
    {
        return 'guardian.roles.manage';
    }

    public function rules(): array
    {
        return [];
    }

    public function persist(): Response
    {
        app(DeletePermissionAction::class)->execute($this->permission());

        return response()->noContent();
    }
}
