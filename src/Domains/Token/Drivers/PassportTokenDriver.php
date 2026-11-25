<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Drivers;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;
use RefactorCircus\Guardian\Domains\Token\Contracts\TokenDriver;
use RefactorCircus\Guardian\Domains\Token\Data\IssuedToken;
use RefactorCircus\Guardian\Domains\Token\Data\TokenSummary;
use RefactorCircus\Guardian\Domains\Token\Exceptions\TokenDriverException;

/**
 * API tokens as Passport's personal access tokens (criterion 47), for users
 * that are `OAuthenticatable`: issued with `createToken($name, $abilities)`,
 * the abilities being scopes. An expiry given is the token's own:
 * Passport's personal access token lifetime is set to it while that one
 * token is made, then put back. Tokens are listed while not revoked or expired, and
 * revoking marks them revoked. The only class in Guardian that names
 * `Laravel\Passport` (D-017).
 */
final class PassportTokenDriver implements TokenDriver
{
    public function issue(Authenticatable $user, string $name, array $abilities, ?CarbonImmutable $expiresAt): IssuedToken
    {
        $user = $this->oauthenticatable($user);
        $previous = Passport::$personalAccessTokensExpireIn;

        if ($expiresAt !== null) {
            Passport::personalAccessTokensExpireIn(CarbonImmutable::now()->diff($expiresAt));
        }

        try {
            $result = $user->createToken($name, $abilities);
        } finally {
            Passport::$personalAccessTokensExpireIn = $previous;
        }

        $token = $result->getToken();

        if (! $token instanceof Token) {
            throw TokenDriverException::unsupportedUser('passport', OAuthenticatable::class);
        }

        return new IssuedToken($this->summary($token), $result->accessToken);
    }

    public function tokens(Authenticatable $user): array
    {
        return array_values($this->active($this->oauthenticatable($user))
            ->latest()->latest('id')->get()
            ->map(fn (Token $token): TokenSummary => $this->summary($token))
            ->all());
    }

    public function revoke(Authenticatable $user, string $id): bool
    {
        $token = $this->active($this->oauthenticatable($user))->whereKey($id)->first();

        return $token instanceof Token && $token->revoke();
    }

    public function revokeAll(Authenticatable $user): int
    {
        return $this->active($this->oauthenticatable($user))->update(['revoked' => true]);
    }

    /**
     * @return Builder<Token>
     */
    private function active(OAuthenticatable $user): Builder
    {
        return $user->tokens()->getQuery()
            ->where('revoked', false)
            ->where(fn (Builder $query): Builder => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * @throws TokenDriverException for a user model Passport cannot issue to
     */
    private function oauthenticatable(Authenticatable $user): OAuthenticatable
    {
        return $user instanceof OAuthenticatable ? $user : throw TokenDriverException::unsupportedUser('passport', OAuthenticatable::class);
    }

    private function summary(Token $token): TokenSummary
    {
        $scopes = $token->getAttribute('scopes');

        return new TokenSummary(
            id: (string) $token->getKey(),
            name: (string) $token->getAttribute('name'),
            abilities: is_array($scopes) ? array_values(array_filter($scopes, is_string(...))) : [],
            lastUsedAt: null,
            expiresAt: $this->date($token->getAttribute('expires_at')),
            createdAt: $this->date($token->getAttribute('created_at')),
        );
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        return $value instanceof DateTimeInterface ? CarbonImmutable::instance($value) : null;
    }
}
