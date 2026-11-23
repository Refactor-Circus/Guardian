<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ConfirmOneTimeCodeAction: the type enrolled and the code sent.
 */
final readonly class ConfirmOneTimeCodeData extends Data
{
    public function __construct(
        public string $type,
        public string $code,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['email', 'sms'])],
            'code' => ['required', 'string', 'max:64'],
        ];
    }
}
