<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for StartTotpEnrolmentAction: an optional label for the
 * authenticator ("Phone").
 */
final readonly class StartTotpEnrolmentData extends Data
{
    public function __construct(
        public ?string $label = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
