<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection\Data;

use RefactorCircus\Keystone\Data\Data;
use SensitiveParameter;

/**
 * Input for ClearLockoutAction: the sign-in identifier (an email, a
 * username) whose lockout to lift; only its HMAC is ever recorded.
 */
final readonly class ClearLockoutData extends Data
{
    public function __construct(
        #[SensitiveParameter] public string $identifier,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Never dumped with the identifier in it.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['identifier' => '[redacted]'];
    }
}
