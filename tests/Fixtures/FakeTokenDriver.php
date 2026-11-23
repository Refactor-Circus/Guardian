<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Fixtures;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Domains\Token\Contracts\TokenDriver;
use RefactorCircus\Guardian\Domains\Token\Data\IssuedToken;
use RefactorCircus\Guardian\Domains\Token\Data\TokenSummary;

/**
 * A `TokenDriver` that keeps its tokens in memory, for tests.
 */
final class FakeTokenDriver implements TokenDriver
{
    /**
     * @var array<string, array{user: string, summary: TokenSummary}>
     */
    public array $tokens = [];

    public int $revokeAllCalls = 0;

    public function issue(Authenticatable $user, string $name, array $abilities, ?CarbonImmutable $expiresAt): IssuedToken
    {
        $id = (string) Str::ulid();
        $summary = new TokenSummary(id: $id, name: $name, abilities: $abilities, lastUsedAt: null, expiresAt: $expiresAt, createdAt: CarbonImmutable::now());
        $this->tokens[$id] = ['user' => $this->key($user), 'summary' => $summary];

        return new IssuedToken(token: $summary, plainTextToken: $id.'|'.Str::random(40));
    }

    public function tokens(Authenticatable $user): array
    {
        return array_values(array_map(
            fn (array $token): TokenSummary => $token['summary'],
            array_filter($this->tokens, fn (array $token): bool => $token['user'] === $this->key($user)),
        ));
    }

    public function revoke(Authenticatable $user, string $id): bool
    {
        if (($this->tokens[$id]['user'] ?? null) !== $this->key($user)) {
            return false;
        }

        unset($this->tokens[$id]);

        return true;
    }

    public function revokeAll(Authenticatable $user): int
    {
        $this->revokeAllCalls++;
        $count = count($this->tokens($user));
        $this->tokens = array_filter($this->tokens, fn (array $token): bool => $token['user'] !== $this->key($user));

        return $count;
    }

    private function key(Authenticatable $user): string
    {
        $key = $user->getAuthIdentifier();

        return is_scalar($key) ? (string) $key : '';
    }
}
