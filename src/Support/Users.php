<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Support;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * The host's user model, for every Guardian domain but SSO and SCIM (which
 * use Roster's): `guardian.users.model`, or the `users` auth provider's model
 * when that is not set.
 */
final class Users
{
    public function __construct(private readonly Repository $config) {}

    /**
     * @return class-string<Model>
     *
     * @throws InvalidArgumentException when neither setting names an Eloquent model
     */
    public function model(): string
    {
        $model = $this->config->get('guardian.users.model') ?? $this->config->get('auth.providers.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException('[guardian.users.model] (or [auth.providers.users.model]) must be an Eloquent model class.');
        }

        return $model;
    }

    public function newModel(): Model
    {
        $model = $this->model();

        return new $model;
    }

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        return $this->newModel()->newQuery();
    }

    public function find(int|string $key): ?Model
    {
        return $this->query()->whereKey($key)->first();
    }

    /**
     * The user with the email, ignoring case; null for none or an empty one.
     */
    public function findByEmail(string $email): ?Model
    {
        if ($email === '') {
            return null;
        }

        $column = $this->newModel()->qualifyColumn('email');
        $matches = fn (Model $user): bool => strcasecmp((string) $this->email($user), $email) === 0;

        // The exact spellings first (indexed); then a case-insensitive LIKE,
        // whose wildcards may fetch extra rows that the match drops.
        return $this->query()->whereIn($column, array_values(array_unique([$email, strtolower($email)])))->get()->first($matches)
            ?? $this->query()->whereLike($column, $email, caseSensitive: false)->get()->first($matches);
    }

    public function email(Model $user): ?string
    {
        $email = $user->getAttribute('email');

        return is_string($email) ? $email : null;
    }

    /**
     * Whether the user's email is verified: as `MustVerifyEmail` says, or by
     * an `email_verified_at` column.
     */
    public function emailVerified(Model $user): bool
    {
        if ($user instanceof MustVerifyEmail) {
            return $user->hasVerifiedEmail();
        }

        return $user->getAttribute('email_verified_at') !== null;
    }
}
