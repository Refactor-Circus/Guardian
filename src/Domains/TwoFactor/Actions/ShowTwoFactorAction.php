<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\TwoFactorStatus;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorShowingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorShownActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\RecoveryCodes;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class ShowTwoFactorAction extends Action
{
    public function __construct(
        private readonly Factors $factors,
        private readonly RecoveryCodes $recoveryCodes,
        private readonly TwoFactorRequirement $requirement,
    ) {}

    /**
     * A user's two-factor status (criterion 31): their methods (never a
     * secret, destinations masked), passkeys, the recovery codes left, and
     * what they are required to use.
     */
    protected function handle(Model&Authenticatable $user): TwoFactorStatus
    {
        TwoFactorShowingActionEvent::dispatch($user);

        $requirement = $this->requirement->for($user);
        $methods = TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))->oldest()->oldest('id')->get();

        $status = new TwoFactorStatus(
            enabled: $this->factors->has($user),
            methods: $methods,
            passkeys: new Collection,
            passkeysAvailable: in_array('passkey', $this->factors->available(), true),
            recoveryCodesRemaining: $this->recoveryCodes->remaining(UserKeys::of($user)),
            requirement: $requirement,
            allowedMethods: array_values(array_intersect($requirement->methods, $this->factors->available())),
        );

        TwoFactorShownActionEvent::dispatch($user);

        return $status;
    }
}
