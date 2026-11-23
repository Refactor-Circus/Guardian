<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use RefactorCircus\Guardian\Support\Users;

/**
 * What an HTTP request or MCP call about two-factor names: the signed-in
 * user, one of their own methods by id (another user's is not found), or a
 * user by key.
 */
trait NamesTwoFactor
{
    private (Model&Authenticatable)|null $namedUser = null;

    /**
     * An argument the caller sent.
     */
    abstract protected function argument(string $key): mixed;

    abstract protected function actor(): ?Model;

    /**
     * The signed-in user the call acts for.
     *
     * @throws AuthenticationException for a guest
     */
    protected function signedIn(): Model&Authenticatable
    {
        $actor = $this->actor();

        return $actor instanceof Authenticatable ? $actor : throw new AuthenticationException;
    }

    /**
     * One of the signed-in user's methods, by the `method` argument.
     */
    protected function ownMethod(): TwoFactorMethodModel
    {
        $id = $this->argument('method');

        return TwoFactorMethodModel::query()->ownedBy(UserKeys::of($this->signedIn()))->whereKey(is_scalar($id) ? (string) $id : '')->firstOrFail();
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
