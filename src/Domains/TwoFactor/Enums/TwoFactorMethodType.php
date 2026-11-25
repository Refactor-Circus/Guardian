<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Enums;

/**
 * The kinds of stored two-factor method (criterion 26). Passkeys are kept in
 * their own table, and recovery codes are not a method.
 */
enum TwoFactorMethodType: string
{
    case Totp = 'totp';
    case Email = 'email';
    case Sms = 'sms';

    /**
     * Every type's value, for validation and schemas.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    /**
     * Whether codes for this type are sent through a `OneTimeCodeChannel`.
     */
    public function sendsCodes(): bool
    {
        return $this !== self::Totp;
    }
}
