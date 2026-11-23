<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\TwoFactor\Data\RemoveTwoFactorMethodData;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorMethodRemovedActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Events\TwoFactorMethodRemovingActionEvent;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\FactorRemoval;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\SecurityLog;
use RefactorCircus\Keystone\Actions\Action;

final class RemoveTwoFactorMethodAction extends Action
{
    public function __construct(
        private readonly FactorRemoval $removal,
        private readonly SecurityLog $log,
    ) {}

    /**
     * Remove one of the user's methods (criterion 31, D-023). A confirmed
     * one needs a current code from another factor, or a recovery code, and
     * cannot be the last one while two-factor is required. One still waiting
     * for confirmation goes without.
     */
    protected function handle(Model&Authenticatable $user, TwoFactorMethodModel $method, RemoveTwoFactorMethodData $data = new RemoveTwoFactorMethodData): void
    {
        TwoFactorMethodRemovingActionEvent::dispatch($method);

        $confirmed = $method->confirmed();

        if ($confirmed) {
            $this->removal->authorize($user, (string) $method->id, $data);
        }

        DB::transaction(function () use ($user, $method, $confirmed): void {
            $method->delete();

            if ($confirmed) {
                $this->removal->afterRemoving($user);

                $this->log->record(SecurityEventType::TwoFactorDisabled, $user, ['method' => $method->type->value, 'factor_id' => $method->id]);
            }
        });

        TwoFactorMethodRemovedActionEvent::dispatch($method);
    }
}
