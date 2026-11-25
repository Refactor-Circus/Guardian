<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token;

use RefactorCircus\Guardian\Domains\Token\Support\TokenPackages;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * API tokens (criteria 44 to 48): `TokenManager` picks the driver from
 * `guardian.tokens.driver` (none by default: the API answers 501), and the
 * `guardian.tokens.*` routes and MCP tools list, issue (HTTP only) and
 * revoke through it. Needs nothing from Roster, Sanctum or Passport.
 */
class TokenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singletonIf(TokenPackages::class);
        $this->app->singleton(TokenManager::class);
    }

    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
