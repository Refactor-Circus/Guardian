<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRegistrationStartedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRegistrationStartingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyCeremony;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class StartPasskeyRegistrationAction extends Action
{
    public function __construct(
        private readonly PasskeysAvailable $available,
        private readonly PasskeyCeremony $ceremony,
    ) {}

    /**
     * Registration options for the browser (criterion 30); the challenge is
     * kept server side for five minutes, bound to the user.
     *
     * @return array<string, mixed>
     */
    protected function handle(Model&Authenticatable $user): array
    {
        PasskeyRegistrationStartingActionEvent::dispatch($user);

        $this->available->ensure();

        $options = $this->ceremony->registrationOptions($user, PasskeyModel::query()->ownedBy(UserKeys::of($user))->get());

        PasskeyRegistrationStartedActionEvent::dispatch($user);

        return $options;
    }
}
