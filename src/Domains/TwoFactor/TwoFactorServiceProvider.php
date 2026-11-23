<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\TwoFactor;

use RefactorCircus\Guardian\Domains\TwoFactor\Contracts\TwoFactorRequirement;
use RefactorCircus\Guardian\Domains\TwoFactor\Models\TwoFactorMethodModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\PolicyTwoFactorRequirement;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * Two-factor authentication (criteria 26 to 34): TOTP, recovery codes,
 * email and SMS codes, passkeys, the sign-in challenge and enforcement.
 * Needs nothing from Roster.
 */
class TwoFactorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // A host binds its own to decide who must use two-factor.
        $this->app->bindIf(TwoFactorRequirement::class, PolicyTwoFactorRequirement::class);
    }

    public function boot(): void
    {
        // An installed audit log keeps no secret, destination or code hash.
        $this->app->make(AuditHooks::class)->redact('secret', 'destination', 'code_hash');

        // The method policy (from `guardian.policies`) answers the
        // `guardian.two-factor.*` checks the API and MCP tools make.
        $this->app->make(Authorization::class)->resource('guardian.two-factor', TwoFactorMethodModel::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
