<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Session;

use Illuminate\Support\Facades\Event;
use RefactorCircus\Guardian\Domains\Session\Listeners\TrackSignIn;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * Sessions and devices (criteria 35 to 38): each sign-in's device and
 * session record, the `guardian.session` middleware that enforces
 * revocation, and the `guardian.sessions.*` and `guardian.devices.*` API
 * and MCP tools. Needs nothing from Roster.
 */
class SessionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::subscribe(TrackSignIn::class);

        // The session policy (from `guardian.policies`) answers the
        // `guardian.sessions.*` checks the API and MCP tools make.
        $this->app->make(Authorization::class)->resource('guardian.sessions', SessionModel::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
