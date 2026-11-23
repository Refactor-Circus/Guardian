<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RefactorCircus\Guardian\Domains\Session\Models\DeviceModel;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Guardian\Support\Users;

/**
 * What an HTTP request or MCP call about sessions names: the signed-in
 * user, one of their own active sessions or devices by id (another user's
 * is not found), or a user by key. Answered with `guardian.sessions.manage`
 * where it is about another user.
 */
trait NamesSessions
{
    private (Model&Authenticatable)|null $namedUser = null;

    /**
     * An argument the caller sent.
     */
    abstract protected function argument(string $key): mixed;

    abstract protected function actor(): ?Model;

    protected function ability(): string
    {
        return 'guardian.sessions.manage';
    }

    /**
     * @throws AuthenticationException for a guest
     */
    protected function signedIn(): Model&Authenticatable
    {
        $actor = $this->actor();

        return $actor instanceof Authenticatable ? $actor : throw new AuthenticationException;
    }

    /**
     * One of the signed-in user's active sessions, by the `session` argument.
     */
    protected function ownSession(): SessionModel
    {
        $id = $this->argument('session');

        return SessionModel::query()->activeFor(UserKeys::of($this->signedIn()))->whereKey(is_scalar($id) ? (string) $id : '')->firstOrFail();
    }

    /**
     * One of the signed-in user's devices, by the `device` argument.
     */
    protected function ownDevice(): DeviceModel
    {
        $id = $this->argument('device');

        return DeviceModel::query()->ownedBy(UserKeys::of($this->signedIn()))->whereKey(is_scalar($id) ? (string) $id : '')->firstOrFail();
    }

    /**
     * The user the `user` argument names.
     */
    protected function namedUser(): Model&Authenticatable
    {
        if ($this->namedUser !== null) {
            return $this->namedUser;
        }

        $key = $this->argument('user');
        $user = is_int($key) || is_string($key) ? app(Users::class)->find($key) : null;

        if (! $user instanceof Authenticatable) {
            throw (new ModelNotFoundException)->setModel(app(Users::class)->model());
        }

        return $this->namedUser = $user;
    }
}
