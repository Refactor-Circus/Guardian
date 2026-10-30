<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Actions;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Sso\Concerns\SsoConnectionRules;
use RefactorCircus\Guardian\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionUpdatedActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Events\SsoConnectionUpdatingActionEvent;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Keystone\Actions\Action;

final class UpdateSsoConnectionAction extends Action
{
    use SsoConnectionRules;

    /**
     * Change a connection. The protocol is fixed; a blank client secret
     * keeps the stored one.
     */
    protected function handle(SsoConnectionModel $connection, UpdateSsoConnectionData $data): SsoConnectionModel
    {
        $settings = $this->settings($connection->protocol, $data, $connection->config);

        SsoConnectionUpdatingActionEvent::dispatch($connection, $data);

        DB::transaction(fn () => $connection->update(
            Arr::only($data->toArray(), ['name', 'slug', 'jit', 'enforced', 'enabled']) + ['config' => $settings],
        ));

        $connection = $connection->refresh()->load('organization')->loadCount('identities');

        SsoConnectionUpdatedActionEvent::dispatch($connection);

        return $connection;
    }
}
