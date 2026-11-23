<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Exceptions;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Exceptions\GuardianException;
use Symfony\Component\HttpFoundation\Response;

/**
 * A password request Guardian refuses, with a machine-readable `code`
 * (`password_expired`, `too_many_attempts`) next to the message.
 */
final class PasswordException extends GuardianException
{
    /**
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $headers
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        int $status,
        public readonly array $extra = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message);

        $this->status = $status;
    }

    /**
     * The password is older than `guardian.passwords.max_age_days`.
     */
    public static function expired(): self
    {
        return new self(__('guardian::guardian.password_expired'), 'password_expired', 403);
    }

    public static function throttled(int $retryAfter): self
    {
        return new self(__('guardian::guardian.two_factor_throttled', ['seconds' => $retryAfter]), 'too_many_attempts', 429, ['retry_after' => $retryAfter], ['Retry-After' => (string) $retryAfter]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): Response
    {
        return new JsonResponse(['message' => $this->getMessage(), 'code' => $this->errorCode] + $this->extra, $this->status, $this->headers);
    }
}
