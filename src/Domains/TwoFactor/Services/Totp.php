<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Config\Repository;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FA\Support\Constants;
use SensitiveParameter;
use Throwable;

/**
 * Time-based one-time passwords (RFC 6238, D-009) through pragmarx/google2fa:
 * SHA-1, 6 digits, 30-second steps, accepted within one step either side.
 * A code is never accepted for a time step at or before the last one used,
 * so a code cannot be replayed. Comparisons are constant-time.
 */
final class Totp
{
    public const int DIGITS = 6;

    public const int PERIOD = 30;

    public const int WINDOW = 1;

    public function __construct(private readonly Repository $config) {}

    /**
     * A new random base32 secret of 160 bits.
     */
    public function generateSecret(): string
    {
        return $this->engine()->generateSecretKey(32);
    }

    /**
     * The `otpauth://` URI an authenticator app reads (usually from a QR code
     * the UI draws): issuer `guardian.two_factor.issuer`, else the app name.
     */
    public function uri(string $account, #[SensitiveParameter] string $secret): string
    {
        return $this->engine()->getQRCodeUrl($this->issuer(), $account, $secret);
    }

    public function issuer(): string
    {
        $issuer = $this->config->get('guardian.two_factor.issuer') ?? $this->config->get('app.name');

        return is_string($issuer) && $issuer !== '' ? $issuer : 'Laravel';
    }

    /**
     * The time step a valid code belongs to, or null when the code is not
     * valid now (±1 step) or belongs to a step at or before `$lastTimestep`.
     */
    public function verify(#[SensitiveParameter] string $secret, #[SensitiveParameter] string $code, ?int $lastTimestep = null, ?int $timestep = null): ?int
    {
        if (preg_match('/^\d{'.self::DIGITS.'}$/', $code) !== 1) {
            return null;
        }

        try {
            // With no step used yet, any step after 0 is newer: the library then
            // answers the matching step rather than `true`.
            $step = $this->engine()->verifyKeyNewer($secret, $code, $lastTimestep ?? 0, self::WINDOW, $timestep ?? $this->timestep());
        } catch (Throwable) {
            return null;
        }

        return is_int($step) ? $step : null;
    }

    /**
     * The code for a time step (now by default).
     */
    public function code(#[SensitiveParameter] string $secret, ?int $timestep = null): string
    {
        return $this->engine()->oathTotp($secret, $timestep ?? $this->timestep());
    }

    /**
     * The current time step.
     */
    public function timestep(): int
    {
        return intdiv(now()->getTimestamp(), self::PERIOD);
    }

    private function engine(): Google2FA
    {
        $engine = new Google2FA;
        $engine->setOneTimePasswordLength(self::DIGITS);
        $engine->setKeyRegeneration(self::PERIOD);
        $engine->setAlgorithm(Constants::SHA1);

        return $engine;
    }
}
