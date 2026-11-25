<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmedFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RegisterPasskeyData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRegisteredActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRegisteringActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeyCeremony;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\TwoFactorEnabling;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class RegisterPasskeyAction extends Action
{
    public function __construct(
        private readonly PasskeysAvailable $available,
        private readonly PasskeyCeremony $ceremony,
        private readonly TwoFactorEnabling $enabling,
    ) {}

    /**
     * Verify the browser's attestation against the options issued to the
     * user and store the credential (criterion 30). The user's first factor
     * also gets their recovery codes, answered this once.
     */
    protected function handle(Model&Authenticatable $user, RegisterPasskeyData $data): ConfirmedFactor
    {
        PasskeyRegisteringActionEvent::dispatch($user);

        $this->available->ensure();

        $credential = $this->ceremony->register($user, $data->credential);

        if (PasskeyModel::query()->where('credential_id', $credential->credentialId)->exists()) {
            throw ValidationException::withMessages(['credential' => __('guardian::guardian.passkey_taken')]);
        }

        [$passkey, $codes] = DB::transaction(function () use ($user, $credential, $data): array {
            $passkey = PasskeyModel::query()->create([
                'user_id' => UserKeys::of($user),
                'credential_id' => $credential->credentialId,
                'public_key' => $credential->publicKey,
                'sign_count' => $credential->signCount,
                'transports' => $credential->transports,
                'aaguid' => $credential->aaguid,
                'label' => $data->label,
            ]);

            return [$passkey, $this->enabling->confirm($user, $passkey)];
        });

        PasskeyRegisteredActionEvent::dispatch($passkey);

        return new ConfirmedFactor($passkey, $codes);
    }
}
