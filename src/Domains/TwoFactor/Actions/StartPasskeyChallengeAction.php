<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyChallengeStartedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyChallengeStartingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyCeremony;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class StartPasskeyChallengeAction extends Action
{
    public function __construct(
        private readonly PasskeysAvailable $available,
        private readonly PasskeyCeremony $ceremony,
    ) {}

    /**
     * Assertion options for the user's passkeys (criterion 32), the
     * challenge kept server side for five minutes, bound to the user.
     *
     * @return array<string, mixed>
     */
    protected function handle(Model&Authenticatable $user): array
    {
        PasskeyChallengeStartingActionEvent::dispatch($user);

        $this->available->ensure();

        $passkeys = PasskeyModel::query()->ownedBy(UserKeys::of($user))->get();

        if ($passkeys->isEmpty()) {
            throw ValidationException::withMessages(['method' => __('guardian::guardian.two_factor_no_such_method')]);
        }

        $options = $this->ceremony->assertionOptions($user, $passkeys);

        PasskeyChallengeStartedActionEvent::dispatch($user);

        return $options;
    }
}
