<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Session\Data\SessionsRevoked;
use RefactorCircus\Guardian\Domains\Session\Events\UserSessionsRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\UserSessionsRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Session\Services\Revocations;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class RevokeUserSessionsAction extends Action
{
    public function __construct(private readonly Revocations $revocations) {}

    /**
     * Revoke every active session of a user, for an admin (criterion 38),
     * each recorded as `session.revoked` naming the admin.
     */
    protected function handle(Authenticatable $user, ?Authenticatable $actor = null): SessionsRevoked
    {
        UserSessionsRevokingActionEvent::dispatch($user);

        $sessions = DB::transaction(fn (): int => $this->revocations->revoke(
            SessionModel::query()->activeFor(UserKeys::of($user))->get(),
            $actor,
        ));

        $result = new SessionsRevoked($sessions);

        UserSessionsRevokedActionEvent::dispatch($user, $result);

        return $result;
    }
}
