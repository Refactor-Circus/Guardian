<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password\Data;

/**
 * Whether a password meets the policy, and the messages saying why not.
 */
final readonly class PasswordCheck
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public bool $valid,
        public array $errors,
    ) {}
}
