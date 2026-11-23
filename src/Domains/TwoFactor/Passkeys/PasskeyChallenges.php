<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Passkeys;

use Illuminate\Contracts\Cache\Repository;

/**
 * The options of a passkey ceremony under way, kept server side for five
 * minutes and bound to the user (criterion 30): a registration or an
 * assertion answers only the challenge Guardian issued to that user, once.
 */
final class PasskeyChallenges
{
    public const int TTL_SECONDS = 300;

    public function __construct(private readonly Repository $cache) {}

    /**
     * Keep a ceremony's options (as JSON), replacing that user's earlier one.
     */
    public function put(string $ceremony, int|string $user, string $options): void
    {
        $this->cache->put($this->key($ceremony, $user), $options, self::TTL_SECONDS);
    }

    /**
     * Take a ceremony's options: answered once, then gone.
     */
    public function pull(string $ceremony, int|string $user): ?string
    {
        $options = $this->cache->pull($this->key($ceremony, $user));

        return is_string($options) ? $options : null;
    }

    private function key(string $ceremony, int|string $user): string
    {
        return 'guardian:passkeys:'.$ceremony.':'.hash('sha256', (string) $user);
    }
}
