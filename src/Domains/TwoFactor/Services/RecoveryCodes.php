<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Services;

use Illuminate\Support\Collection;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\RecoveryCodeModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Support\CodeHash;
use SensitiveParameter;

/**
 * Recovery codes (criterion 28): ten codes shaped `xxxxx-xxxxx`, stored only
 * as HMACs, each accepted once. The plain codes exist only in the answer
 * that creates them.
 */
final class RecoveryCodes
{
    public const int COUNT = 10;

    private const string ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /**
     * Replace the user's codes with new ones and answer them in plain text,
     * the only time they are seen. Call inside a transaction.
     *
     * @return list<string>
     */
    public function replace(int|string $user): array
    {
        $this->forget($user);

        $codes = [];

        for ($i = 0; $i < self::COUNT; $i++) {
            $code = $this->generate();
            $codes[] = $code;

            RecoveryCodeModel::query()->create(['user_id' => $user, 'code_hash' => CodeHash::of($code, 'recovery')]);
        }

        return $codes;
    }

    /**
     * Use one of the user's codes: true, and marked used, when an unused code
     * matches. Every unused code is compared in constant time.
     */
    public function consume(int|string $user, #[SensitiveParameter] string $code): bool
    {
        $code = $this->normalize($code);

        if (preg_match('/^[a-z0-9]{5}-[a-z0-9]{5}$/', $code) !== 1) {
            return false;
        }

        $match = null;

        foreach ($this->unused($user) as $stored) {
            if (CodeHash::matches($stored->code_hash, $code, 'recovery')) {
                $match = $stored;
            }
        }

        if ($match === null) {
            return false;
        }

        // Only the request that flips it from unused wins.
        return RecoveryCodeModel::query()->whereKey($match->id)->whereNull('used_at')->update(['used_at' => now()]) === 1;
    }

    public function remaining(int|string $user): int
    {
        return RecoveryCodeModel::query()->where('user_id', $user)->whereNull('used_at')->count();
    }

    public function forget(int|string $user): void
    {
        RecoveryCodeModel::query()->where('user_id', $user)->get()->each->delete();
    }

    /**
     * Whether a typed code has a recovery code's shape (rather than a
     * six-digit one).
     */
    public function looksLikeOne(#[SensitiveParameter] string $code): bool
    {
        return str_contains($code, '-') && strlen(trim($code)) === 11;
    }

    /**
     * @return Collection<int, RecoveryCodeModel>
     */
    private function unused(int|string $user): Collection
    {
        return RecoveryCodeModel::query()->where('user_id', $user)->whereNull('used_at')->get();
    }

    private function generate(): string
    {
        $characters = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < 10; $i++) {
            $characters .= self::ALPHABET[random_int(0, $max)];
        }

        return substr($characters, 0, 5).'-'.substr($characters, 5);
    }

    private function normalize(#[SensitiveParameter] string $code): string
    {
        return strtolower(trim($code));
    }
}
