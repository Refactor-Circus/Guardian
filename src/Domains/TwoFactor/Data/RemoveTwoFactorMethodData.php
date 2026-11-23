<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for removing a factor (D-023): a current code from another confirmed
 * factor, or a recovery code, or an assertion by another passkey (made
 * against the challenge's passkey options).
 */
final readonly class RemoveTwoFactorMethodData extends Data
{
    /**
     * @param  array<string, mixed>|null  $assertion
     */
    public function __construct(
        public ?string $code = null,
        public ?array $assertion = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'assertion' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
