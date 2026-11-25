<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Data;

/**
 * The active password policy, read from `guardian.passwords`.
 */
final readonly class PasswordPolicySettings
{
    public function __construct(
        public int $minLength,
        public int $maxLength,
        public bool $mixedCase,
        public bool $numbers,
        public bool $symbols,
        public bool $letters,
        public bool $uncompromised,
        public int $uncompromisedThreshold,
        public int $history,
        public ?int $maxAgeDays,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            minLength: self::integer('min_length', 12),
            maxLength: self::integer('max_length', 128),
            mixedCase: config('guardian.passwords.mixed_case') === true,
            numbers: config('guardian.passwords.numbers') === true,
            symbols: config('guardian.passwords.symbols') === true,
            letters: config('guardian.passwords.letters') === true,
            uncompromised: config('guardian.passwords.uncompromised', true) !== false,
            uncompromisedThreshold: self::integer('uncompromised_threshold', 0),
            history: max(0, self::integer('history', 5)),
            maxAgeDays: is_numeric($days = config('guardian.passwords.max_age_days')) ? (int) $days : null,
        );
    }

    private static function integer(string $key, int $default): int
    {
        $value = config('guardian.passwords.'.$key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }
}
