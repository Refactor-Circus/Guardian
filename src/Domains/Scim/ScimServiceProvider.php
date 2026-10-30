<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Scim;

use Illuminate\Contracts\Config\Repository;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Guardian\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Guardian\Domains\Scim\Services\ScimContext;
use RefactorCircus\Guardian\Domains\Scim\Support\ScimAudit;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Audit\Contracts\AuditTrail;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Keystone\Support\Surface;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use Throwable;

/**
 * SCIM 2.0 provisioning and the tokens that authenticate it.
 *
 * Registered only while a Roster with the seams is installed. Everything
 * reaches Roster through its public seams: the `guardian.scim` resource and
 * the `guardian.scim.manage` permission, granted to admins and answered by
 * the token policy from `guardian.policies`, and the `scim` membership
 * source the members SCIM provisions join with. Its audit entries get the
 * organization Roster gave them, and Roster's entries the token a change
 * came through.
 *
 * The SCIM server routes and the morph aliases for Roster's old class names
 * wait for `Handover::complete()`: while Roster still ships SCIM it serves
 * the same URIs and owns those names (D-022, D-027, criterion 31).
 */
class ScimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One per request (and per queued job or Octane request), never shared.
        $this->app->scoped(ScimContext::class);

        // Calls to the SCIM server come through the `scim` surface, as they
        // did in Roster. The shared surface is scoped, so name the prefix
        // each time it is resolved.
        $this->app->afterResolving(Surface::class, function (Surface $surface): void {
            $surface->route('guardian.scim.', 'scim');
        });
    }

    public function boot(): void
    {
        $this->registerAuthorization();

        $this->app->make(ScimAudit::class)->register($this->app->make(AuditHooks::class));

        // Once every provider has booted, so a Roster that still registers
        // these itself has done so first.
        $this->app->booted(function (): void {
            $this->registerMembershipSource();
            $this->registerAuditContext();
        });

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if (! $this->app->make(Handover::class)->complete()) {
            return;
        }

        // Keen entries stored while Roster owned these models resolve to
        // Guardian's (models/stored-identifiers).
        $this->keepMorphAliases([
            'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimTokenModel' => ScimTokenModel::class,
            'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimUserModel' => ScimUserModel::class,
            'RefactorCircus\\Roster\\Domains\\Scim\\Models\\ScimGroupModel' => ScimGroupModel::class,
        ]);

        if ($this->app->make(Repository::class)->get('guardian.scim.enabled') === true) {
            $this->loadRoutesFrom(__DIR__.'/scim.php');
        }
    }

    /**
     * The `guardian.scim` resource and its permission, through the seams
     * every package uses. Policies come from `guardian.policies`.
     */
    private function registerAuthorization(): void
    {
        $this->app->make(Resources::class)->resource('guardian.scim', ScimTokenModel::class);

        $this->app->make(PermissionCatalogue::class)
            ->permission('guardian.scim.manage', 'Issue and revoke SCIM provisioning tokens.', ['admin']);
    }

    /**
     * Members SCIM provisions join with the `scim` source, unless something
     * (a Roster that still ships SCIM) has registered it already.
     */
    private function registerMembershipSource(): void
    {
        $sources = $this->app->make(MembershipSources::class);

        if (! $sources->has('scim')) {
            $sources->register('scim', 'SCIM');
        }
    }

    /**
     * While an audit log records, the SCIM token a change came through joins
     * the context of the entries of the packages in
     * `guardian.scim.audit_context_packages`.
     */
    private function registerAuditContext(): void
    {
        if (! $this->app->make(AuditTrail::class)->available()) {
            return;
        }

        $this->app->make(AuditHooks::class)->context(fn (object $event): array => $this->auditContext($event));
    }

    /**
     * The SCIM token behind an entry. AuditHooks runs every context hook for
     * every package's events and catches nothing, so this keeps to the listed
     * packages and never breaks the entry: a failure is reported and the
     * entry is written without the token.
     *
     * @return array<string, mixed>
     */
    private function auditContext(object $event): array
    {
        try {
            $packages = (array) $this->app->make(Repository::class)->get('guardian.scim.audit_context_packages', []);

            if (! in_array($this->app->make(PackageRegistry::class)->for($event)?->key, $packages, true)) {
                return [];
            }

            $token = $this->app->make(ScimContext::class)->token;

            return $token === null ? [] : ['scim_token' => ['id' => $token->id, 'name' => $token->name]];
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }
}
