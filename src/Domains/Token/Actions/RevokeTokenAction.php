<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\Token\Events\TokenRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Token\Events\TokenRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Token\Services\TokenLog;
use RefactorCircus\Guardian\Domains\Token\TokenManager;
use RefactorCircus\Keystone\Actions\Action;

final class RevokeTokenAction extends Action
{
    public function __construct(
        private readonly TokenManager $tokens,
        private readonly TokenLog $log,
    ) {}

    /**
     * Revoke one of the user's API tokens (criterion 48); one they do not
     * hold is not found. Recorded as `token.revoked`.
     *
     * @throws ModelNotFoundException
     */
    protected function handle(Authenticatable $user, string $tokenId, ?Authenticatable $actor = null): void
    {
        TokenRevokingActionEvent::dispatch($user, $tokenId);

        $driver = $this->tokens->driver();

        DB::transaction(function () use ($driver, $user, $tokenId, $actor): void {
            if (! $driver->revoke($user, $tokenId)) {
                throw new ModelNotFoundException;
            }

            $this->log->record(SecurityEventType::TokenRevoked, $user, $actor, ['id' => $tokenId]);
        });

        TokenRevokedActionEvent::dispatch($user, $tokenId);
    }
}
