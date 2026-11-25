<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRemovedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\PasskeyRemovingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\PasskeyModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Passkeys\PasskeysAvailable;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\FactorRemoval;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;
use RefactorCircus\Keystone\Actions\Action;

final class RemovePasskeyAction extends Action
{
    public function __construct(
        private readonly PasskeysAvailable $available,
        private readonly FactorRemoval $removal,
        private readonly SecurityLog $log,
    ) {}

    /**
     * Remove one of the user's passkeys (criteria 30 and 31, D-023): with a
     * current code from another factor, a recovery code or another passkey's
     * assertion, and never the last factor while two-factor is required.
     */
    protected function handle(Model&Authenticatable $user, PasskeyModel $passkey, RemoveTwoFactorMethodData $data = new RemoveTwoFactorMethodData): void
    {
        PasskeyRemovingActionEvent::dispatch($passkey);

        $this->available->ensure();
        $this->removal->authorize($user, $passkey->id, $data);

        DB::transaction(function () use ($user, $passkey): void {
            $passkey->delete();
            $this->removal->afterRemoving($user);

            $this->log->record(SecurityEventType::TwoFactorDisabled, $user, ['method' => 'passkey', 'factor_id' => $passkey->id]);
        });

        PasskeyRemovedActionEvent::dispatch($passkey);
    }
}
