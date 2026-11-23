<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\OneTimeCodeChannel;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\OneTimeCodeModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\CodeHash;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\UserKeys;
use SensitiveParameter;

/**
 * Email and SMS codes (criterion 29, D-011): six random digits sent through
 * the bound `OneTimeCodeChannel`, kept only as an HMAC, valid for
 * `guardian.two_factor.one_time_codes.ttl_minutes` and five attempts, used
 * once. A newer code replaces the older ones. Sends are limited to one per
 * 60 seconds per user.
 */
final class OneTimeCodes
{
    public const int MAX_ATTEMPTS = 5;

    public const int RESEND_SECONDS = 60;

    public function __construct(
        private readonly OneTimeCodeChannel $channel,
        private readonly Repository $config,
    ) {}

    public function supports(string $type): bool
    {
        return $this->channel->supports($type);
    }

    /**
     * Throw unless the user may be sent a code now (one per minute).
     *
     * @throws TwoFactorException 429 with the seconds to wait
     */
    public function ensureCanSend(Authenticatable $user): void
    {
        $key = $this->limiterKey($user);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw TwoFactorException::throttled(max(RateLimiter::availableIn($key), 1));
        }
    }

    /**
     * Make a new code for the method, replacing any still waiting, and send
     * it. Counts towards the one-per-minute limit.
     */
    public function send(Authenticatable $user, TwoFactorMethodModel $method): void
    {
        $this->ensureCanSend($user);

        $destination = $method->destination;

        if ($destination === null || ! $this->channel->supports($method->type->value)) {
            throw TwoFactorException::methodUnavailable($method->type->value);
        }

        RateLimiter::hit($this->limiterKey($user), self::RESEND_SECONDS);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($method, $code): void {
            OneTimeCodeModel::query()->where('method_id', $method->id)->whereNull('consumed_at')->get()->each->delete();

            OneTimeCodeModel::query()->create([
                'user_id' => $method->user_id,
                'method_id' => $method->id,
                'code_hash' => CodeHash::of($code, 'otp:'.$method->id),
                'expires_at' => now()->addMinutes($this->ttlMinutes()),
            ]);
        });

        $this->channel->send($user, $method->type->value, $destination, $code);
    }

    /**
     * Use the method's current code: true, and consumed, when it matches,
     * has not expired and has attempts left; a wrong code uses one attempt.
     */
    public function verify(TwoFactorMethodModel $method, #[SensitiveParameter] string $code): bool
    {
        $stored = OneTimeCodeModel::query()->where('method_id', $method->id)->whereNull('consumed_at')->latest('expires_at')->first();

        if ($stored === null || $stored->expires_at->isPast() || $stored->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (preg_match('/^\d{6}$/', $code) !== 1 || ! CodeHash::matches($stored->code_hash, $code, 'otp:'.$method->id)) {
            OneTimeCodeModel::query()->whereKey($stored->id)->increment('attempts');

            return false;
        }

        // Only the request that consumes it wins.
        return OneTimeCodeModel::query()->whereKey($stored->id)->whereNull('consumed_at')->where('attempts', '<', self::MAX_ATTEMPTS)
            ->update(['consumed_at' => now()]) === 1;
    }

    public function ttlMinutes(): int
    {
        $minutes = $this->config->get('guardian.two_factor.one_time_codes.ttl_minutes', 10);

        return is_numeric($minutes) ? max((int) $minutes, 1) : 10;
    }

    private function limiterKey(Authenticatable $user): string
    {
        return 'guardian-two-factor-send:'.UserKeys::of($user);
    }
}
