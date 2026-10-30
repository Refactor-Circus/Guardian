<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains;

use Illuminate\Support\ServiceProvider;
use RefactorCircus\Guardian\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Guardian\Domains\Sso\SsoServiceProvider;

/**
 * Registers every domain module's service provider. GuardianServiceProvider
 * registers this only while a Roster Guardian can work with is installed.
 */
final class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var list<class-string<ServiceProvider>>
     */
    private array $providers = [
        SsoServiceProvider::class,
        ScimServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
