<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for removing a factor (D-023): a current code from another confirmed
 * factor, or a recovery code.
 */
final readonly class RemoveTwoFactorMethodData extends Data
{
    public function __construct(
        public ?string $code = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}
