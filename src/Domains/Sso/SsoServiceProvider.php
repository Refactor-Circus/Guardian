<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Guardian\Domains\Sso\Services\Sso;
use RefactorCircus\Guardian\Domains\Sso\Services\SsoSignInRequirement;
use RefactorCircus\Guardian\Domains\Sso\Support\SsoAudit;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Authorization\Abilities;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Roster;

/**
 * Single sign-on connections (OIDC, SAML, Entra ID) and linked identities.
 *
 * Registered only while a Roster with the seams is installed. The
 * `guardian.sso.*` abilities are defined in Keystone's `Abilities` (granted
 * to `admin` by a role store that creates it) and answered by the connection
 * policy from `guardian.policies`, in the connection's Roster organization.
 * Roster is reached through its public seams: the sign-in requirement that
 * holds an enforced connection's domains to single sign-on. Its audit
 * entries get the subject and organization Roster gave them.
 *
 * The sign-in routes and the morph aliases for Roster's old class names wait
 * for `Handover::complete()`: while Roster still ships SSO it serves the
 * same URIs and owns those names (D-022, D-027, criterion 31).
 */
class SsoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerAuthorization();

        $this->app->make(SsoAudit::class)->register($this->app->make(AuditHooks::class));

        // Enforced connections hold their domains to single sign-on, whether
        // or not the sign-in routes are available here.
        $this->app->make(Roster::class)->requireSignIn(SsoSignInRequirement::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if (! $this->app->make(Handover::class)->complete()) {
            return;
        }

        // Keen entries stored while Roster owned these models resolve to
        // Guardian's (models/stored-identifiers).
        $this->keepMorphAliases([
            'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoConnectionModel' => SsoConnectionModel::class,
            'RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoIdentityModel' => SsoIdentityModel::class,
        ]);

        if (! $this->app->make(Sso::class)->available()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/web.php');

        // SAML identity providers post the signed assertion cross-site;
        // the assertion's signature, not a CSRF token, protects it.
        ValidateCsrfToken::except([trim((string) $this->app->make(Repository::class)->get('guardian.sso.prefix', 'roster/sso'), '/').'/*/callback']);
    }

    /**
     * The `guardian.sso.*` abilities, defined once per application, and the
     * connection policy as the answer to them. Policies come from
     * `guardian.policies`.
     */
    private function registerAuthorization(): void
    {
        $this->app->make(Authorization::class)->resource('guardian.sso', SsoConnectionModel::class);

        $abilities = $this->app->make(Abilities::class);

        foreach ([
            'guardian.sso.view' => "View an organization's single sign-on connections.",
            'guardian.sso.manage' => 'Configure single sign-on connections.',
        ] as $ability => $description) {
            if (! $abilities->has($ability)) {
                $abilities->define($ability, $description, ['admin']);
            }
        }
    }
}
