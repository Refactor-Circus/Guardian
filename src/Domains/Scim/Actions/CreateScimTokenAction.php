<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Guardian\Domains\Scim\Data\IssuedScimToken;
use RefactorCircus\Guardian\Domains\Scim\Events\ScimTokenCreatedActionEvent;
use RefactorCircus\Guardian\Domains\Scim\Events\ScimTokenCreatingActionEvent;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Keystone\Actions\Action;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class CreateScimTokenAction extends Action
{
    /**
     * Issue a token an identity provider uses to provision the organization.
     * With `sso_connection`, provisioned users' externalIds become that
     * connection's SSO subjects.
     */
    protected function handle(OrganizationModel $organization, CreateScimTokenData $data, ?Model $actor = null): IssuedScimToken
    {
        $connection = null;

        if ($data->ssoConnection !== null && $data->ssoConnection !== '') {
            $connection = SsoConnectionModel::query()
                ->where('organization_id', $organization->getKey())
                ->where('slug', $data->ssoConnection)
                ->first() ?? throw ValidationException::withMessages(['sso_connection' => __('guardian::guardian.unknown_sso_connection')]);
        }

        ScimTokenCreatingActionEvent::dispatch($organization, $data);

        $plain = 'scim_'.Str::random(48);

        $token = DB::transaction(fn (): ScimTokenModel => ScimTokenModel::query()->create([
            'organization_id' => $organization->getKey(),
            'name' => $data->name,
            'token_hash' => hash('sha256', $plain),
            'sso_connection_id' => $connection?->getKey(),
            'created_by' => $actor?->getKey(),
            'expires_at' => $data->expiresInDays !== null ? now()->addDays($data->expiresInDays) : null,
        ]));

        $token->load(['organization', 'ssoConnection']);

        ScimTokenCreatedActionEvent::dispatch($token);

        return new IssuedScimToken($token, $plain);
    }
}
