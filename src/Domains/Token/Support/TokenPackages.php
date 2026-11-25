<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Support;

use Composer\InstalledVersions;

/**
 * Whether a token driver's package is installed (criterion 45). Neither
 * Sanctum nor Passport is required; tests bind their own answer.
 */
class TokenPackages
{
    public function installed(string $package): bool
    {
        return InstalledVersions::isInstalled($package);
    }
}
