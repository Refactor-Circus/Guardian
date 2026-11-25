<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Drivers;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use RefactorCircus\Guardian\Domains\Token\Contracts\TokenDriver;
use RefactorCircus\Guardian\Domains\Token\Data\IssuedToken;
use RefactorCircus\Guardian\Domains\Token\Data\TokenSummary;
use RefactorCircus\Guardian\Domains\Token\Exceptions\TokenDriverException;

/**
 * API tokens as Sanctum's personal access tokens (criterion 46), for users
 * using Sanctum's `HasApiTokens`: issued through `createToken()` with their
 * abilities and expiry, listed while they work, revoked by deleting them.
 * The only class in Guardian that names `Laravel\Sanctum` (D-017).
 */
final class SanctumTokenDriver implements TokenDriver
{
    public function issue(Authenticatable $user, string $name, array $abilities, ?CarbonImmutable $expiresAt): IssuedToken
    {
        $user = $this->tokenable($user);

        $issued = method_exists($user, 'createToken') ? $user->createToken($name, $abilities, $expiresAt) : null;

        if (! $issued instanceof NewAccessToken) {
            throw TokenDriverException::unsupportedUser('sanctum', HasApiTokens::class);
        }

        return new IssuedToken($this->summary($issued->accessToken), $issued->plainTextToken);
    }

    public function tokens(Authenticatable $user): array
    {
        return array_values($this->query($this->tokenable($user))
            ->where(fn (Builder $query): Builder => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()->latest('id')->get()
            ->map(fn (PersonalAccessToken $token): TokenSummary => $this->summary($token))
            ->all());
    }

    public function revoke(Authenticatable $user, string $id): bool
    {
        return $this->query($this->tokenable($user))->whereKey($id)->delete() > 0;
    }

    public function revokeAll(Authenticatable $user): int
    {
        return (int) $this->query($this->tokenable($user))->delete();
    }

    /**
     * @throws TokenDriverException for a user model without Sanctum's tokens
     */
    private function tokenable(Authenticatable $user): Model
    {
        if (! $user instanceof Model || ! in_array(HasApiTokens::class, class_uses_recursive($user), true)) {
            throw TokenDriverException::unsupportedUser('sanctum', HasApiTokens::class);
        }

        return $user;
    }

    /**
     * @return Builder<PersonalAccessToken>
     */
    private function query(Model $user): Builder
    {
        /** @var class-string<PersonalAccessToken> $model */
        $model = Sanctum::personalAccessTokenModel();

        return $model::query()->where('tokenable_type', $user->getMorphClass())->where('tokenable_id', $user->getKey());
    }

    private function summary(PersonalAccessToken $token): TokenSummary
    {
        $abilities = $token->getAttribute('abilities');

        return new TokenSummary(
            id: (string) $token->getKey(),
            name: (string) $token->getAttribute('name'),
            abilities: is_array($abilities) ? array_values(array_filter($abilities, is_string(...))) : [],
            lastUsedAt: $this->date($token->getAttribute('last_used_at')),
            expiresAt: $this->date($token->getAttribute('expires_at')),
            createdAt: $this->date($token->getAttribute('created_at')),
        );
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        return $value instanceof DateTimeInterface ? CarbonImmutable::instance($value) : null;
    }
}
