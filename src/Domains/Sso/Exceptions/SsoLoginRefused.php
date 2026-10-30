<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Exceptions;

use RefactorCircus\Guardian\Exceptions\GuardianException;

/**
 * Why an identity provider's sign-in was refused. Internal to
 * SsoLoginAction, which turns it into a validation error after recording it.
 */
final class SsoLoginRefused extends GuardianException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
