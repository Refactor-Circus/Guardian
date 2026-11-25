<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session\Services;

use Illuminate\Contracts\Session\Session;
use SensitiveParameter;

/**
 * The random key a signed-in session holds (criterion 36, D-018). Only an
 * HMAC of it, keyed by the app key, is stored, so neither the key nor the
 * session id (a bearer secret) is ever written to Guardian's tables. The key
 * lives in the session's data, so it survives the session id being
 * regenerated.
 */
final class SessionKeys
{
    public const string KEY = 'guardian.session_key';

    /**
     * Put a new key in the session; its hash.
     */
    public function issue(Session $session): string
    {
        $key = bin2hex(random_bytes(32));
        $session->put(self::KEY, $key);

        return $this->hash($key);
    }

    /**
     * The hash of the key the session holds, if it holds one.
     */
    public function current(?Session $session): ?string
    {
        $key = $session?->get(self::KEY);

        return is_string($key) && $key !== '' ? $this->hash($key) : null;
    }

    public function forget(Session $session): void
    {
        $session->forget(self::KEY);
    }

    public function hash(#[SensitiveParameter] string $key): string
    {
        $appKey = config('app.key');

        return hash_hmac('sha256', 'session|'.$key, is_string($appKey) ? $appKey : '');
    }
}
