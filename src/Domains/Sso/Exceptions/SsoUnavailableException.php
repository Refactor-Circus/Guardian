<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso\Exceptions;

use RefactorCircus\Guardian\Exceptions\GuardianException;

/**
 * A connection's protocol needs optional packages that are not installed.
 */
final class SsoUnavailableException extends GuardianException
{
    public static function forProtocol(string $protocol): self
    {
        $packages = match ($protocol) {
            'saml' => 'laravel/socialite socialiteproviders/saml2',
            'azure' => 'laravel/socialite socialiteproviders/microsoft-azure',
            default => 'laravel/socialite firebase/php-jwt',
        };

        return new self("Single sign-on with [{$protocol}] needs: composer require {$packages}");
    }
}
