<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Domains\Sso\Concerns\SsoConnectionRules;
use RefactorCircus\Guardian\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionCreatedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionCreatingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Keystone\Actions\Action;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class CreateSsoConnectionAction extends Action
{
    use SsoConnectionRules;

    /**
     * Connect an organization to its identity provider.
     *
     * OIDC needs `issuer`, `client_id`, `client_secret`; Microsoft Entra ID
     * (`azure`) needs `tenant`, `client_id`, `client_secret`; SAML needs
     * `metadata_url`, or `entity_id`, `sso_url` and `certificate`.
     */
    protected function handle(OrganizationModel $organization, CreateSsoConnectionData $data): SsoConnectionModel
    {
        $protocol = $data->protocol;
        $settings = $this->settings($protocol, $data);

        SsoConnectionCreatingActionEvent::dispatch($organization, $data);

        $connection = DB::transaction(fn (): SsoConnectionModel => SsoConnectionModel::query()->create([
            'organization_id' => $organization->getKey(),
            'name' => $data->name,
            'slug' => $data->slug !== null && $data->slug !== ''
                ? $data->slug
                : $this->uniqueSlug($organization->slug.'-'.$data->name, SsoConnectionModel::query()),
            'protocol' => $protocol,
            'config' => $settings,
            'jit' => $data->jit,
            'enforced' => $data->enforced,
            'enabled' => $data->enabled,
        ]));

        $connection->load('organization')->loadCount('identities');

        SsoConnectionCreatedActionEvent::dispatch($connection);

        return $connection;
    }

    /**
     * A slug from the name, numbered until no connection has it.
     *
     * @param  Builder<SsoConnectionModel>  $scope
     */
    private function uniqueSlug(string $name, Builder $scope): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? Str::lower(Str::random(8)) : $base;
        $slug = $base;

        for ($suffix = 2; (clone $scope)->where('slug', $slug)->exists(); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
