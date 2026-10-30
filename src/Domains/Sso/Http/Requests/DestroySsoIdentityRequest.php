<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Guardian\Domains\Sso\Actions\UnlinkSsoIdentityAction;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Http\Requests\Request;

final class DestroySsoIdentityRequest extends Request
{
    protected function ability(): string
    {
        return 'guardian.sso.manage';
    }

    private ?SsoIdentityModel $resolved = null;

    protected function scope(): ?OrganizationModel
    {
        return $this->identity()->connection?->organization;
    }

    /**
     * Anyone may unlink their own identity.
     */
    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $this->identity()->ownedBy($actor) ? $actor : null;
    }

    private function identity(): SsoIdentityModel
    {
        return $this->resolved ??= SsoIdentityModel::query()->whereKey($this->route('identity'))->firstOrFail();
    }

    public function persist(): Response
    {
        app(UnlinkSsoIdentityAction::class)->execute($this->identity());

        return response()->noContent();
    }
}
