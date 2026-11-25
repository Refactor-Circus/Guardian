<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\StartTotpEnrolmentData;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\TotpEnrolment;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TotpEnrolmentStartedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TotpEnrolmentStartingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Totp;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Actions\Action;

final class StartTotpEnrolmentAction extends Action
{
    public function __construct(
        private readonly Totp $totp,
        private readonly Users $users,
    ) {}

    /**
     * Start enrolling an authenticator app (criterion 27): a new secret on an
     * unconfirmed method, replacing one still waiting, answered once with its
     * `otpauth://` URI. Nothing counts until it is confirmed.
     */
    protected function handle(Model&Authenticatable $user, StartTotpEnrolmentData $data = new StartTotpEnrolmentData): TotpEnrolment
    {
        TotpEnrolmentStartingActionEvent::dispatch($user);

        $secret = $this->totp->generateSecret();
        $key = UserKeys::of($user);

        $method = DB::transaction(function () use ($key, $secret, $data): TwoFactorMethodModel {
            TwoFactorMethodModel::query()->ownedBy($key)->where('type', TwoFactorMethodType::Totp->value)->whereNull('confirmed_at')->get()->each->delete();

            return TwoFactorMethodModel::query()->create([
                'user_id' => $key,
                'type' => TwoFactorMethodType::Totp,
                'secret' => $secret,
                'label' => $data->label,
            ]);
        });

        TotpEnrolmentStartedActionEvent::dispatch($method);

        return new TotpEnrolment($method, $secret, $this->totp->uri($this->users->email($user) ?? (string) $key, $secret));
    }
}
