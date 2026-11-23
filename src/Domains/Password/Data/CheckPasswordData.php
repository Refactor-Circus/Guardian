<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Data;

use RefactorCircus\Keystone\Data\Data;
use SensitiveParameter;

/**
 * Input for CheckPasswordAction: the password to check, which is never
 * stored, logged or put in an event.
 */
final readonly class CheckPasswordData extends Data
{
    public function __construct(
        #[SensitiveParameter] public string $password,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:4096'],
        ];
    }

    /**
     * Never dumped with the password in it.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['password' => '[redacted]'];
    }
}
