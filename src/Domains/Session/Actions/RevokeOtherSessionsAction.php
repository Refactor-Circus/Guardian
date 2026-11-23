<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\Session\Data\RevokeOtherSessionsData;
use RefactorCircus\Guardian\Domains\Session\Data\SessionsRevoked;
use RefactorCircus\Guardian\Domains\Session\Events\OtherSessionsRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Session\Events\OtherSessionsRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\Session\Services\Revocations;
use RefactorCircus\Guardian\Domains\Token\Contracts\TokenDriver;
use RefactorCircus\Guardian\Domains\Token\Exceptions\TokenDriverException;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Keystone\Actions\Action;

final class RevokeOtherSessionsAction extends Action
{
    public function __construct(private readonly Revocations $revocations) {}

    /**
     * Sign out everywhere else (criterion 38): every active session of the
     * user but the one whose key hash is given (none over MCP, so all go).
     * With `tokens`, the user's API tokens are revoked through the token
     * driver too; without a driver nothing is revoked and a
     * `TokenDriverException` (501) says so.
     */
    protected function handle(Authenticatable $user, RevokeOtherSessionsData $data = new RevokeOtherSessionsData, ?string $currentKeyHash = null): SessionsRevoked
    {
        OtherSessionsRevokingActionEvent::dispatch($user, $data);

        $driver = $data->tokens ? $this->driver() : null;

        $sessions = DB::transaction(fn (): int => $this->revocations->revoke(
            SessionModel::query()->activeFor(UserKeys::of($user))
                ->when($currentKeyHash !== null, fn (Builder $query): Builder => $query->where('key_hash', '!=', $currentKeyHash))
                ->get(),
            $user,
        ));

        $result = new SessionsRevoked($sessions, $driver?->revokeAll($user));

        OtherSessionsRevokedActionEvent::dispatch($user, $result);

        return $result;
    }

    private function driver(): TokenDriver
    {
        return app()->bound(TokenDriver::class) ? app(TokenDriver::class) : throw TokenDriverException::none();
    }
}
