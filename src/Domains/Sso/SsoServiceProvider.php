<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Sso;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Guardian\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Guardian\Domains\Sso\Services\Sso;
use RefactorCircus\Guardian\Domains\Sso\Services\SsoSignInRequirement;
use RefactorCircus\Guardian\Domains\Sso\Support\SsoAudit;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Roster;

/**
 * Single sign-on connections (OIDC, SAML, Entra ID) and linked identities.
 *
 * Registered only while a Roster with the seams is installed. Everything
 * reaches Roster through its public seams: the `guardian.sso` resource and
 * the `guardian.sso.*` permissions, granted to admins and answered by the
 * connection policy from `guardian.policies`, and the sign-in requirement
 * that holds an enforced connection's domains to single sign-on. Its audit
 * entries get the subject and organization Roster gave them.
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

        if (! $this->app->make(Sso::class)->available()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/web.php');

        // SAML identity providers post the signed assertion cross-site;
        // the assertion's signature, not a CSRF token, protects it.
        ValidateCsrfToken::except([trim((string) $this->app->make(Repository::class)->get('guardian.sso.prefix', 'roster/sso'), '/').'/*/callback']);
    }

    /**
     * The `guardian.sso` resource and its permissions, through the seams
     * every package uses. Policies come from `guardian.policies`.
     */
    private function registerAuthorization(): void
    {
        $this->app->make(Resources::class)->resource('guardian.sso', SsoConnectionModel::class);

        $this->app->make(PermissionCatalogue::class)
            ->permission('guardian.sso.view', "View an organization's single sign-on connections.", ['admin'])
            ->permission('guardian.sso.manage', 'Configure single sign-on connections.', ['admin']);
    }
}
