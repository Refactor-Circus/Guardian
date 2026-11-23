<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Exceptions;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Exceptions\GuardianException;
use Symfony\Component\HttpFoundation\Response;

/**
 * A two-factor request Guardian refuses, with a machine-readable `code` a
 * front end acts on (`two_factor_required`, `method_unavailable`, ...) next
 * to the message. Over MCP the message is the error.
 */
final class TwoFactorException extends GuardianException
{
    /**
     * @param  array<string, mixed>  $extra  more fields for the JSON answer
     * @param  array<string, string>  $headers
     */
    public function __construct(
        string $message,
        public readonly string $errorCode,
        int $status = 422,
        public readonly array $extra = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message);

        $this->status = $status;
    }

    /**
     * Removing the last factor while two-factor is required (criterion 31).
     */
    public static function required(): self
    {
        return new self(__('guardian::guardian.two_factor_required'), 'two_factor_required');
    }

    /**
     * A method type nothing here can deliver (criterion 29).
     */
    public static function methodUnavailable(string $type): self
    {
        return new self(__('guardian::guardian.two_factor_method_unavailable', ['type' => $type]), 'method_unavailable');
    }

    /**
     * Too many attempts or sends; try again after the given seconds.
     */
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
