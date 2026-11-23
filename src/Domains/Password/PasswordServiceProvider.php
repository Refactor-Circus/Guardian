<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Password;

use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Validation\NotPwnedVerifier;
use InvalidArgumentException;
use RefactorCircus\Guardian\Domains\Password\Models\PasswordHistoryModel;
use RefactorCircus\Guardian\Domains\Password\Observers\RecordPasswordChange;
use RefactorCircus\Guardian\Domains\Password\Support\BreachCheck;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Guardian\Support\Users;
use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * The password policy (criteria 39 to 43): `PasswordPolicy::rules()` for
 * the host's requests, the breach check that records outages, the history
 * kept by an observer on the user model, the `guardian.password-fresh`
 * middleware and the policy and check endpoints. Needs nothing from
 * Roster.
 */
class PasswordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Laravel's own verifier only: a host's (or a test's) binding wins.
        $this->app->extend(UncompromisedVerifier::class, fn (UncompromisedVerifier $verifier): UncompromisedVerifier => $verifier::class === NotPwnedVerifier::class ? new BreachCheck : $verifier);
    }

    public function boot(): void
    {
        try {
            $this->app->make(Users::class)->model()::observe(RecordPasswordChange::class);
        } catch (InvalidArgumentException) {
            // No user model configured yet: nothing to watch.
        }

        $this->app->make(Authorization::class)->resource('guardian.passwords', PasswordHistoryModel::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
