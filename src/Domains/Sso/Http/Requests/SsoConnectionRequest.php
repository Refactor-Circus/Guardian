<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Http\Requests\Request;

abstract class SsoConnectionRequest extends Request
{
    private ?SsoConnectionModel $resolved = null;

    protected function connection(): SsoConnectionModel
    {
        return $this->resolved ??= SsoConnectionModel::query()->where('slug', $this->route('connection'))->firstOrFail();
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->connection()->organization;
    }
}
