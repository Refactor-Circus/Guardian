<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RefactorCircus\Guardian\Support\Users;

/**
 * What an HTTP request or MCP call about API tokens names: the signed-in
 * user, a token id, or a user by key. Answered with
 * `guardian.tokens.manage` where it is about another user.
 */
trait NamesTokens
{
    private (Model&Authenticatable)|null $namedUser = null;

    /**
     * An argument the caller sent.
     */
    abstract protected function argument(string $key): mixed;

    abstract protected function actor(): ?Model;

    protected function ability(): string
    {
        return 'guardian.tokens.manage';
    }

    /**
     * @throws AuthenticationException for a guest
     */
    protected function signedIn(): Model&Authenticatable
    {
        $actor = $this->actor();

        return $actor instanceof Authenticatable ? $actor : throw new AuthenticationException;
    }

    protected function tokenId(): string
    {
        $id = $this->argument('token');

        return is_scalar($id) ? (string) $id : '';
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

    protected function actingUser(): ?Authenticatable
    {
        $actor = $this->actor();

        return $actor instanceof Authenticatable ? $actor : null;
    }
}
