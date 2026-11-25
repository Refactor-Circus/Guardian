<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\RateLimiter;
use RefactorCircus\Guardian\Domains\SecurityEvent\Support\IdentifierHash;

/**
 * Failed sign-ins (criteria 49 to 51), counted in the rate limiter (the
 * default cache store): per identifier, keyed by its HMAC so the typed value
 * is never stored, and per address. Three counters: the identifier's and
 * the address's lockout windows (`guardian.login.decay_minutes`), and the
 * identifier's failures in the past hour, which a sign-in does not clear and
 * which make a sign-in from a new device suspicious.
 */
final class LoginFailures
{
    public const int RECENT_SECONDS = 3600;

    public function __construct(private readonly Repository $config) {}

    public function failed(?string $identifier, ?string $ip): void
    {
        $decay = $this->decayMinutes() * 60;

        if ($identifier !== null && $identifier !== '') {
            RateLimiter::hit($this->identifierKey($identifier), $decay);
            RateLimiter::hit($this->recentKey($identifier), self::RECENT_SECONDS);
        }

        if ($ip !== null && $ip !== '') {
            RateLimiter::hit($this->ipKey($ip), $decay);
        }
    }

    /**
     * Seconds until the identifier or address may try again; null when
     * neither is locked out.
     */
    public function lockedOutFor(?string $identifier, ?string $ip): ?int
    {
        $waits = [];

        if ($identifier !== null && $identifier !== '' && RateLimiter::tooManyAttempts($this->identifierKey($identifier), $this->integer('max_attempts', 5))) {
            $waits[] = RateLimiter::availableIn($this->identifierKey($identifier));
        }

        if ($ip !== null && $ip !== '' && RateLimiter::tooManyAttempts($this->ipKey($ip), $this->integer('ip_max_attempts', 50))) {
            $waits[] = RateLimiter::availableIn($this->ipKey($ip));
        }

        return $waits === [] ? null : max(1, ...$waits);
    }

    /**
     * The identifier's failures in its lockout window.
     */
    public function attempts(string $identifier): int
    {
        return RateLimiter::attempts($this->identifierKey($identifier));
    }

    /**
     * The identifier's failures in the past hour.
     */
    public function recent(string $identifier): int
    {
        return RateLimiter::attempts($this->recentKey($identifier));
    }

    /**
     * Lift the identifier's lockout (a sign-in, or an admin).
     */
    public function clear(string $identifier): void
    {
        RateLimiter::clear($this->identifierKey($identifier));
    }

    private function identifierKey(string $identifier): string
    {
        return 'guardian-login:identifier:'.IdentifierHash::of($identifier);
    }

    private function recentKey(string $identifier): string
    {
        return 'guardian-login:recent:'.IdentifierHash::of($identifier);
    }

    private function ipKey(string $ip): string
    {
        return 'guardian-login:ip:'.$ip;
    }

    private function decayMinutes(): int
    {
        return max(1, $this->integer('decay_minutes', 15));
    }

    private function integer(string $key, int $default): int
    {
        $value = $this->config->get('guardian.login.'.$key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }
}
