<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for PassChallengeAction: the kind of factor answered with, and a
 * `code` (TOTP, email, SMS or recovery) or a passkey `assertion`.
 */
final readonly class PassChallengeData extends Data
{
    public const array METHODS = ['totp', 'recovery', 'email', 'sms', 'passkey'];

    /**
     * @param  array<string, mixed>|null  $assertion
     */
    public function __construct(
        public string $method,
        public ?string $code = null,
        public ?array $assertion = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'method' => ['required', Rule::in(self::METHODS)],
            'code' => ['required_unless:method,passkey', 'nullable', 'string', 'max:64'],
            'assertion' => ['required_if:method,passkey', 'nullable', 'array'],
        ];
    }
}
