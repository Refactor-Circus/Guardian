<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmedFactor;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\ConfirmOneTimeCodeData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\OneTimeCodeConfirmedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\OneTimeCodeConfirmingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\OneTimeCodes;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\TwoFactorEnabling;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class ConfirmOneTimeCodeAction extends Action
{
    public function __construct(
        private readonly OneTimeCodes $codes,
        private readonly TwoFactorEnabling $enabling,
    ) {}

    /**
     * Confirm an enrolled email address or phone number with the code sent
     * to it. The user's first confirmed factor also gets their recovery
     * codes, answered this once.
     */
    protected function handle(Model&Authenticatable $user, ConfirmOneTimeCodeData $data): ConfirmedFactor
    {
        $method = TwoFactorMethodModel::query()->ownedBy(UserKeys::of($user))
            ->where('type', $data->type)
            ->whereNull('confirmed_at')
            ->latest()
            ->first() ?? throw ValidationException::withMessages(['code' => __('guardian::guardian.two_factor_nothing_pending')]);

        OneTimeCodeConfirmingActionEvent::dispatch($method);

        // Outside the transaction, so a wrong code's attempt is kept.
        if (! $this->codes->verify($method, trim($data->code))) {
            throw ValidationException::withMessages(['code' => __('guardian::guardian.two_factor_invalid_code')]);
        }

        $codes = DB::transaction(fn (): ?array => $this->enabling->confirm($user, $method));

        OneTimeCodeConfirmedActionEvent::dispatch($method);

        return new ConfirmedFactor($method, $codes);
    }
}
