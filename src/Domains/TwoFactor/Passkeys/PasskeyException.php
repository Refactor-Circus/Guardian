<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Passkeys;

use Illuminate\Validation\ValidationException;

/**
 * A passkey ceremony that failed: no challenge waiting (or it expired), a
 * response the library could not verify, or a signature counter that did
 * not move forward.
 */
final class PasskeyException extends ValidationException
{
    public bool $signCountMismatch = false;

    public static function failed(string $field, string $message, bool $signCountMismatch = false): self
    {
        $exception = self::withMessages([$field => $message]);
        $exception->signCountMismatch = $signCountMismatch;

        return $exception;
    }
}
