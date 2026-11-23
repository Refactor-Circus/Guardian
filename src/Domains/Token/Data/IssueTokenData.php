<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for IssueTokenAction: the token's name, its abilities (within
 * `guardian.tokens.abilities`; all of those when left out) and when it
 * expires (no later than `guardian.tokens.max_lifetime_days` from now; that
 * limit when left out).
 */
final readonly class IssueTokenData extends Data
{
    /**
     * @param  list<string>|null  $abilities
     */
    public function __construct(
        public string $name,
        public ?array $abilities = null,
        public ?Carbon $expiresAt = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $latest = self::latestExpiry();
        $expiry = ['sometimes', 'nullable', 'date', 'after:now'];

        if ($latest !== null) {
            $expiry[] = 'before_or_equal:'.$latest->toIso8601String();
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['sometimes', 'nullable', 'array', 'min:1'],
            'abilities.*' => ['string', 'distinct', Rule::in(self::allowedAbilities())],
            'expires_at' => $expiry,
        ];
    }

    /**
     * The abilities a token may be given.
     *
     * @return list<string>
     */
    public static function allowedAbilities(): array
    {
        $abilities = config('guardian.tokens.abilities', ['*']);

        return is_array($abilities) ? array_values(array_filter($abilities, is_string(...))) : ['*'];
    }

    /**
     * The latest a token issued now may expire; null for no limit.
     */
    public static function latestExpiry(): ?CarbonImmutable
    {
        $days = config('guardian.tokens.max_lifetime_days', 365);

        return is_numeric($days) ? CarbonImmutable::now()->addDays((int) $days) : null;
    }
}
