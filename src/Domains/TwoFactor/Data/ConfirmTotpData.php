<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ConfirmTotpAction: a current code from the new authenticator.
 */
final readonly class ConfirmTotpData extends Data
{
    public function __construct(
        public string $code,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
        ];
    }
}
