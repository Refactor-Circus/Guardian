<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains;

use Illuminate\Support\ServiceProvider;
use RefactorCircus\Guardian\Domains\LoginProtection\LoginProtectionServiceProvider;
use RefactorCircus\Guardian\Domains\Password\PasswordServiceProvider;
use RefactorCircus\Guardian\Domains\Permission\PermissionServiceProvider;
use RefactorCircus\Guardian\Domains\Role\RoleServiceProvider;
use RefactorCircus\Guardian\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Guardian\Domains\SecurityEvent\SecurityEventServiceProvider;
use RefactorCircus\Guardian\Domains\Session\SessionServiceProvider;
use RefactorCircus\Guardian\Domains\Sso\SsoServiceProvider;
use RefactorCircus\Guardian\Domains\Token\TokenServiceProvider;
use RefactorCircus\Guardian\Domains\TwoFactor\TwoFactorServiceProvider;
use RefactorCircus\Guardian\Support\Integration;

/**
 * Registers every domain module's service provider. Every domain registers
 * whether or not Roster is installed, except SSO and SCIM, which work on
 * Roster's users and organizations and so wait for
 * `Integration::rosterInstalled()` (criterion 3).
 */
final class DomainServiceProvider extends ServiceProvider
{
    /**
     * The domains that need nothing from Roster.
     *
     * @var list<class-string<ServiceProvider>>
     */
    private array $providers = [
        RoleServiceProvider::class,
        PermissionServiceProvider::class,
        SecurityEventServiceProvider::class,
        TwoFactorServiceProvider::class,
        SessionServiceProvider::class,
        PasswordServiceProvider::class,
        TokenServiceProvider::class,
        LoginProtectionServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }

        if ($this->app->make(Integration::class)->rosterInstalled()) {
            $this->app->register(SsoServiceProvider::class);
            $this->app->register(ScimServiceProvider::class);
        }
    }
}
