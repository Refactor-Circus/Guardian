<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\LoginProtection;

use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleDeletedEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Events\IpRuleSavedEvent;
use RefactorCircus\Guardian\Domains\LoginProtection\Listeners\CountLoginFailures;
use RefactorCircus\Guardian\Domains\LoginProtection\Services\IpRules;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * Login protection (criteria 49 to 52): failed sign-ins counted for the
 * `guardian.login-throttle` lockout, suspicious sign-ins (through the
 * session domain's sign-in listener), IP rules enforced by `guardian.ip`,
 * and the `guardian.lockouts.*` and `guardian.ip-rules.*` API and MCP
 * tools. Needs nothing from Roster.
 */
class LoginProtectionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::subscribe(CountLoginFailures::class);

        // Any write to the rules refreshes the cached copy.
        Event::listen([IpRuleSavedEvent::class, IpRuleDeletedEvent::class], function (): void {
            $this->app->make(IpRules::class)->forget();
        });

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
