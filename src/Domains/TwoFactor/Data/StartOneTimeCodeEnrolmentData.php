<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for StartOneTimeCodeEnrolmentAction: `email` with an address, or
 * `sms` with a phone number in E.164 form (`+447700900123`), and an
 * optional label.
 */
final readonly class StartOneTimeCodeEnrolmentData extends Data
{
    public function __construct(
        public string $type,
        public string $destination,
        public ?string $label = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['email', 'sms'])],
            'destination' => [
                'required',
                'string',
                'max:255',
                Rule::when(fn (Fluent $input): bool => $input->get('type') === 'email', ['email']),
                Rule::when(fn (Fluent $input): bool => $input->get('type') === 'sms', ['regex:/^\+[1-9]\d{6,14}$/']),
            ],
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
