<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Session\Events\SessionRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\SessionRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Session\Services\Revocations;
use RefactorCircus\Keystone\Actions\Action;

final class RevokeSessionAction extends Action
{
    public function __construct(private readonly Revocations $revocations) {}

    /**
     * Revoke one session (criterion 38): it is signed out on its next
     * request through `guardian.session`. Recorded as `session.revoked`.
     */
    protected function handle(SessionModel $session, ?Authenticatable $actor = null): void
    {
        SessionRevokingActionEvent::dispatch($session);

        DB::transaction(fn (): int => $this->revocations->revoke([$session], $actor));

        SessionRevokedActionEvent::dispatch($session);
    }
}
