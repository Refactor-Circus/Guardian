<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmedFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmTotpData;
use RefactorCircus\Guardian\Domains\TwoFactor\Enums\TwoFactorMethodType;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TotpConfirmedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TotpConfirmingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\TwoFactorEnabling;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class ConfirmTotpAction extends Action
{
    public function __construct(
        private readonly Factors $factors,
        private readonly TwoFactorEnabling $enabling,
    ) {}

    /**
     * Confirm the authenticator waiting for it with a current code (±1 time
     * step, never a step used before). The user's first confirmed factor also
     * gets their recovery codes, answered this once.
     */
    protected function handle(Model&Authenticatable $user, ConfirmTotpData $data): ConfirmedFactor
    {
        $method = TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))
            ->where('type', TwoFactorMethodType::Totp->value)
            ->whereNull('confirmed_at')
            ->latest()
            ->first() ?? throw ValidationException::withMessages(['code' => __('guardian::guardian.two_factor_nothing_pending')]);

        TotpConfirmingActionEvent::dispatch($method);

        $codes = DB::transaction(function () use ($user, $method, $data): ?array {
            if (! $this->factors->useTotp($method, $data->code)) {
                throw ValidationException::withMessages(['code' => __('guardian::guardian.two_factor_invalid_code')]);
            }

            return $this->enabling->confirm($user, $method);
        });

        TotpConfirmedActionEvent::dispatch($method);

        return new ConfirmedFactor($method, $codes);
    }
}
