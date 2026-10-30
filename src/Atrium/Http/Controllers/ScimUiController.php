<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Atrium\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Guardian\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Guardian\Atrium\Screens;
use RefactorCircus\Guardian\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Guardian\Domains\Scim\Actions\RevokeScimTokenAction;
use RefactorCircus\Guardian\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;

/**
 * Atrium: issue and revoke an organization's SCIM tokens.
 */
final class ScimUiController
{
    use AuthorizesScreens;

    /**
     * The session key holding a new token's plain value, flashed for exactly
     * one page view.
     */
    public const string TOKEN = 'guardian_scim_token';

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen($request, 'guardian.scim.manage', $model);

        $issued = app(CreateScimTokenAction::class)->execute(
            $model,
            CreateScimTokenData::from($request->all()),
            $this->actor($request),
        );

        return $this->toOrganization($model, Screens::SCIM, 'guardian::guardian.scim_token_created')
            ->with(self::TOKEN, $issued->plain);
    }

    public function revoke(Request $request, string $token): RedirectResponse
    {
        $model = ScimTokenModel::query()->whereKey($token)->firstOrFail();
        $this->authorizeScreen($request, 'guardian.scim.manage', $model->organization);

        app(RevokeScimTokenAction::class)->execute($model);

        return $this->toOrganization($model->organization, Screens::SCIM, 'guardian::guardian.scim_token_revoked');
    }
}
