<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorResetActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorResettingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\RecoveryCodes;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class ResetTwoFactorAction extends Action
{
    public function __construct(
        private readonly RecoveryCodes $recoveryCodes,
        private readonly SecurityLog $log,
    ) {}

    /**
     * An admin's reset of another user's two-factor (criterion 31), for a
     * lost device: every method, passkey and recovery code goes, recorded as
     * `two_factor.disabled` with who reset it.
     */
    protected function handle(Model&Authenticatable $user, ?Model $actor = null): void
    {
        TwoFactorResettingActionEvent::dispatch($user);

        $key = UserKeys::of($user);

        DB::transaction(function () use ($user, $key, $actor): void {
            TwoFactorMethodModel::query()->ownedBy($key)->get()->each->delete();
            $this->recoveryCodes->forget($key);

            $this->log->record(SecurityEventType::TwoFactorDisabled, $user, ['reset' => true, 'reset_by' => $actor?->getKey() === null ? null : (string) $actor->getKey()]);
        });

        TwoFactorResetActionEvent::dispatch($user);
    }
}
