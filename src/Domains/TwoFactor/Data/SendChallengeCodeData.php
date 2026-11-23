<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for SendChallengeCodeAction: which kind of code to send.
 */
final readonly class SendChallengeCodeData extends Data
{
    public function __construct(
        public string $method,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'method' => ['required', Rule::in(['email', 'sms'])],
        ];
    }
}
