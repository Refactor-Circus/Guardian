<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\SecurityEvent;

use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\SecurityEvent\Listeners\RecordAuthEvents;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * The security event log (criteria 22 to 25): Laravel's sign-in events and
 * Guardian's own, recorded by `RecordSecurityEventAction`, read through the
 * `guardian.security-events.*` routes and MCP tools. Needs nothing from
 * Roster.
 */
class SecurityEventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::subscribe(RecordAuthEvents::class);

        // The security event policy (from `guardian.policies`) answers the
        // `guardian.security.*` checks the API and MCP tools make.
        $this->app->make(Authorization::class)->resource('guardian.security', SecurityEventModel::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
