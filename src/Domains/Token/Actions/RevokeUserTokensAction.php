<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\Token\Events\UserTokensRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\UserTokensRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Token\Services\TokenLog;
use RefactorCircus\Guardian\Domains\Token\TokenManager;
use RefactorCircus\Keystone\Actions\Action;

final class RevokeUserTokensAction extends Action
{
    public function __construct(
        private readonly TokenManager $tokens,
        private readonly TokenLog $log,
    ) {}

    /**
     * Revoke every API token of a user, for an admin (criterion 48):
     * recorded once as `token.revoked` with the number revoked.
     */
    protected function handle(Authenticatable $user, ?Authenticatable $actor = null): int
    {
        UserTokensRevokingActionEvent::dispatch($user);

        $driver = $this->tokens->driver();

        $revoked = DB::transaction(function () use ($driver, $user, $actor): int {
            $revoked = $driver->revokeAll($user);

            if ($revoked > 0) {
                $this->log->record(SecurityEventType::TokenRevoked, $user, $actor, ['all' => true, 'count' => $revoked]);
            }

            return $revoked;
        });

        UserTokensRevokedActionEvent::dispatch($user, $revoked);

        return $revoked;
    }
}
