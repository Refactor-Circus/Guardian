<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\LoginProtection\Data\ClearLockoutData;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\LockoutClearedActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\LockoutClearingActionEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Services\LoginFailures;
use RefactorCircus\Guardian\Domains\SecurityEvent\Actions\RecordSecurityEventAction;
use RefactorCircus\Guardian\Domains\SecurityEvent\Data\RecordSecurityEventData;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Support\IdentifierHash;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class ClearLockoutAction extends Action
{
    public function __construct(
        private readonly LoginFailures $failures,
        private readonly RecordSecurityEventAction $record,
    ) {}

    /**
     * Lift an identifier's sign-in lockout (criterion 50), recorded as
     * `lockout.cleared` with the identifier's HMAC and who cleared it. The
     * events carry only the HMAC.
     */
    protected function handle(ClearLockoutData $data, ?Authenticatable $actor = null): void
    {
        $hash = IdentifierHash::of($data->identifier);

        LockoutClearingActionEvent::dispatch($hash);

        $this->failures->clear($data->identifier);

        $request = app()->bound('request') ? app('request') : null;

        DB::transaction(fn (): mixed => $this->record->execute(new RecordSecurityEventData(
            type: SecurityEventType::LockoutCleared,
            identifier: $data->identifier,
            ip: $request instanceof Request ? $request->ip() : null,
            userAgent: $request instanceof Request ? $request->userAgent() : null,
            context: $actor === null ? [] : ['by' => (string) UserKeys::of($actor)],
        )));

        LockoutClearedActionEvent::dispatch($hash);
    }
}
