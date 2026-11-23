<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for RegisterPasskeyAction: the browser's new PublicKeyCredential as
 * JSON (base64url fields), and an optional label.
 */
final readonly class RegisterPasskeyData extends Data
{
    /**
     * @param  array<string, mixed>  $credential
     */
    public function __construct(
        public array $credential,
        public ?string $label = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string', 'max:1400'],
            'credential.rawId' => ['required', 'string', 'max:1400'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
            'credential.clientExtensionResults' => ['sometimes', 'array'],
            'credential.authenticatorAttachment' => ['sometimes', 'nullable', 'string'],
            'label' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
