<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\RecoveryCodesRegeneratedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\RecoveryCodesRegeneratingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\Factors;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\RecoveryCodes;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class RegenerateRecoveryCodesAction extends Action
{
    public function __construct(
        private readonly Factors $factors,
        private readonly RecoveryCodes $recoveryCodes,
        private readonly SecurityLog $log,
    ) {}

    /**
     * Replace every recovery code (criterion 28) and answer the new ones
     * this once. Only for a user with two-factor on.
     *
     * @return list<string>
     */
    protected function handle(Model&Authenticatable $user): array
    {
        RecoveryCodesRegeneratingActionEvent::dispatch($user);

        if (! $this->factors->has($user)) {
            throw new TwoFactorException(__('guardian::guardian.two_factor_not_enabled'), 'two_factor_not_enabled');
        }

        $codes = DB::transaction(function () use ($user): array {
            $codes = $this->recoveryCodes->replace(UserKeys::of($user));

            $this->log->record(SecurityEventType::TwoFactorRecoveryCodesRegenerated, $user);

            return $codes;
        });

        RecoveryCodesRegeneratedActionEvent::dispatch($user);

        return $codes;
    }
}
