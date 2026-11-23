<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Guardian\Domains\Session\Data\ListDevicesData;
use RefactorCircus\Guardian\Domains\Session\Events\DevicesListedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\DevicesListingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class ListDevicesAction extends Action
{
    /**
     * The signed-in user's devices, most recently seen first (criterion 38).
     *
     * @return LengthAwarePaginator<int, DeviceModel>
     */
    protected function handle(Authenticatable $user, ListDevicesData $filters = new ListDevicesData): LengthAwarePaginator
    {
        DevicesListingActionEvent::dispatch($user, $filters);

        $devices = DeviceModel::query()->ownedBy(UserKeys::of($user))
            ->latest('last_seen_at')->latest('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);

        DevicesListedActionEvent::dispatch($user, $filters);

        return $devices;
    }
}
