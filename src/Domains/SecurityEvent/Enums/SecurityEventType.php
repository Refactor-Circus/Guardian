<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent\Enums;

/**
 * Every kind of security event Guardian records (criterion 23).
 */
enum SecurityEventType: string
{
    case LoginSucceeded = 'login.succeeded';
    case LoginFailed = 'login.failed';
    case LoginLockedOut = 'login.locked_out';
    case LoginSuspicious = 'login.suspicious';
    case Logout = 'logout';
    case PasswordReset = 'password.reset';
    case PasswordChanged = 'password.changed';
    case PasswordBreachCheckUnavailable = 'password.breach_check_unavailable';
    case TwoFactorEnabled = 'two_factor.enabled';
    case TwoFactorDisabled = 'two_factor.disabled';
    case TwoFactorChallengePassed = 'two_factor.challenge_passed';
    case TwoFactorChallengeFailed = 'two_factor.challenge_failed';
    case TwoFactorRecoveryCodeUsed = 'two_factor.recovery_code_used';
    case TwoFactorRecoveryCodesRegenerated = 'two_factor.recovery_codes_regenerated';
    case TwoFactorPolicyConflict = 'two_factor.policy_conflict';
    case PasskeySignCountMismatch = 'passkey.sign_count_mismatch';
    case SessionRevoked = 'session.revoked';
    case DeviceNew = 'device.new';
    case TokenIssued = 'token.issued';
    case TokenRevoked = 'token.revoked';
    case IpDenied = 'ip.denied';
    case LockoutCleared = 'lockout.cleared';

    /**
     * Every type's value, for validation and schemas.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
