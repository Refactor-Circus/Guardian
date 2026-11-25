<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor\Data;

use Carbon\CarbonImmutable;

/**
 * What a `TwoFactorRequirement` answers for one user: whether two-factor is
 * required, the method types that count (`totp`, `email`, `sms`, `passkey`)
 * and the enrolment deadline (null: already due, when required).
 */
final readonly class Requirement
{
    /**
     * Every method type there is.
     *
     * @var list<string>
     */
    public const array ALL_METHODS = ['totp', 'email', 'sms', 'passkey'];

    /**
     * @param  list<string>  $methods
     */
    public function __construct(
        public bool $required = false,
        public array $methods = self::ALL_METHODS,
        public ?CarbonImmutable $deadline = null,
    ) {}

    /**
     * Whether a user without a factor must enrol before going on: required,
     * and the deadline (if any) has passed.
     */
    public function due(): bool
    {
        return $this->required && ($this->deadline === null || ! $this->deadline->isFuture());
    }
}
