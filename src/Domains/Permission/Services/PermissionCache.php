<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Permission\Services;

use Closure;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository;

/**
 * Keeps the permission names and each user's grants between requests, while
 * `guardian.permission_cache.ttl` is above 0 (criterion 13), in
 * `guardian.permission_cache.store` (the default cache store when null).
 *
 * Entries are versioned: forgetting everyone bumps the grants version, and a
 * catalogue change bumps the names version too, so stale entries are never
 * read again and simply expire. Ported from Roster v0.1's.
 */
final readonly class PermissionCache
{
    private const string NAMES_VERSION = 'guardian:permissions:names-version';

    private const string GRANTS_VERSION = 'guardian:permissions:grants-version';

    public function __construct(
        private Repository $config,
        private Factory $cache,
    ) {}

    public function enabled(): bool
    {
        return $this->ttl() > 0;
    }

    /**
     * @param  Closure(): list<string>  $load
     * @return list<string>
     */
    public function names(Closure $load): array
    {
        if (! $this->enabled()) {
            return $load();
        }

        $store = $this->store();

        /** @var list<string> */
        return $store->remember('guardian:permissions:'.$this->version($store, self::NAMES_VERSION).':names', $this->ttl(), $load);
    }

    /**
     * @template T of array
     *
     * @param  Closure(): T  $load
     * @return T
     */
    public function grants(string $user, Closure $load): array
    {
        if (! $this->enabled()) {
            return $load();
        }

        $store = $this->store();

        /** @var T */
        return $store->remember($this->grantsKey($store, $user), $this->ttl(), $load);
    }

    public function forgetUser(string $user): void
    {
        if ($this->enabled()) {
            $store = $this->store();
            $store->forget($this->grantsKey($store, $user));
        }
    }

    /**
     * Forget every user's grants, and the names too when the catalogue
     * changed.
     */
    public function forget(bool $names = false): void
    {
        if (! $this->enabled()) {
            return;
        }

        $store = $this->store();
        $store->forever(self::GRANTS_VERSION, bin2hex(random_bytes(8)));

        if ($names) {
            $store->forever(self::NAMES_VERSION, bin2hex(random_bytes(8)));
        }
    }

    private function grantsKey(Cache $store, string $user): string
    {
        return 'guardian:permissions:'.$this->version($store, self::NAMES_VERSION).':'.$this->version($store, self::GRANTS_VERSION).':grants:'.$user;
    }

    private function version(Cache $store, string $key): string
    {
        $version = $store->get($key);

        return is_string($version) ? $version : '0';
    }

    private function ttl(): int
    {
        $ttl = $this->config->get('guardian.permission_cache.ttl', 0);

        return is_numeric($ttl) ? (int) $ttl : 0;
    }

    private function store(): Cache
    {
        $store = $this->config->get('guardian.permission_cache.store');

        return $this->cache->store(is_string($store) && $store !== '' ? $store : null);
    }
}
