<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeysListedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeysListingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class ListPasskeysAction extends Action
{
    public function __construct(private readonly PasskeysAvailable $available) {}

    /**
     * The user's passkeys, oldest first.
     *
     * @return Collection<int, PasskeyModel>
     */
    protected function handle(Model&Authenticatable $user): Collection
    {
        PasskeysListingActionEvent::dispatch($user);

        $this->available->ensure();

        $passkeys = PasskeyModel::query()->ownedBy(UserKeys::of($user))->oldest()->oldest('id')->get();

        PasskeysListedActionEvent::dispatch($user);

        return $passkeys;
    }
}
