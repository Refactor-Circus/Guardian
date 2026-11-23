<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use RefactorCircus\Guardian\Console\Commands\AdoptRosterRolesCommand;
use RefactorCircus\Guardian\Console\Commands\UpgradeFromRosterCommand;
use RefactorCircus\Guardian\Domains\DomainServiceProvider;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Services\GuardianRoles;
use RefactorCircus\Guardian\Domains\Session\Http\Middleware\TrackSession;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Middleware\EnsureTwoFactor;
use RefactorCircus\Guardian\Exceptions\StrandedTablesException;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Support\Authorization;
use RefactorCircus\Guardian\Support\Integration;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Guardian\Upgrade\RosterUpgrade;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Support\PackageServiceProvider;
use Throwable;

/**
 * Guardian's package provider.
 *
 * Guardian's domains need nothing from refactor-circus/roster except SSO and
 * SCIM, which work on Roster's users and organizations: their providers,
 * their (Roster-backed) migrations, the upgrade from Roster and the stranded
 * tables warning wait on `Integration::rosterInstalled()` (criterion 3,
 * D-027). Whether Roster is there is asked once the application boots, not
 * while providers register, so a bound Integration (tests, or an app) is the
 * one asked.
 */
class GuardianServiceProvider extends PackageServiceProvider
{
    protected function definition(): Package
    {
        return Package::make('guardian', __NAMESPACE__)
            ->label('Guardian')
            ->server(GuardianServer::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/guardian.php', 'guardian');

        $this->registerPackage();

        $this->app->singleton(Guardian::class);
        $this->app->singleton(Integration::class);
        $this->app->singleton(Handover::class);

        $this->app->singleton(Authorization::class);

        $this->registerRoleStore();

        // Before any provider boots, so the domain providers boot with the
        // rest; DomainServiceProvider skips SSO and SCIM without Roster.
        $this->app->booting(function (): void {
            $this->app->register(DomainServiceProvider::class);
        });
    }

    public function boot(): void
    {
        $this->registerGate();
        $this->registerMiddleware();

        $this->registerRateLimiter();
        $this->registerAbout();

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'guardian');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/guardian.php' => config_path('guardian.php'),
            ], ['guardian', 'guardian-config']);

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/guardian'),
            ], ['guardian', 'guardian-lang']);

            // Roster's old role tables are plain tables: adopting them needs
            // no Roster installed.
            $this->commands([AdoptRosterRolesCommand::class]);
        }

        $this->registerPolicies();
        $this->registerNotFoundResponses();
        $this->registerMcpServer();
        $this->registerCortex();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], ['guardian', 'guardian-migrations']);
        }

        $this->bootWithRoster();
    }

    /**
     * Guardian's role store, per request (criterion 9, D-008). With
     * `guardian.authorization.bind` on (the default) it answers Keystone's
     * `Permissions` and `Roles` for every package; decorators other packages
     * add with `extend()` (Roster's impersonation guard) still wrap it.
     */
    private function registerRoleStore(): void
    {
        $this->app->scoped(GuardianPermissions::class);
        $this->app->scoped(GuardianRoles::class);

        if (! $this->binds()) {
            return;
        }

        $this->app->scoped(Permissions::class, fn (Application $app): Permissions => $app->make(GuardianPermissions::class));
        $this->app->scoped(Roles::class, fn (Application $app): Roles => $app->make(GuardianRoles::class));
    }

    /**
     * Guardian's place in Laravel's Gate while it is the bound role store
     * (criterion 14): `true` for a super-admin, and for an ability Guardian
     * has a permission row for when the user holds it (in the first model
     * argument, or globally); otherwise `null`, so the app's own gates and
     * policies still decide. Never `false`.
     */
    private function registerGate(): void
    {
        if (! $this->binds()) {
            return;
        }

        Gate::before(function (mixed $user, string $ability, array $arguments): ?bool {
            if (! $user instanceof Authenticatable) {
                return null;
            }

            $permissions = $this->app->make(Permissions::class);

            if ($permissions->isSuperAdmin($user)) {
                return true;
            }

            if (! in_array($ability, $this->app->make(GuardianPermissions::class)->known(), true)) {
                return null;
            }

            $scope = array_values(array_filter($arguments, fn (mixed $argument): bool => $argument instanceof Model))[0] ?? null;

            return $permissions->allows($user, $ability, $scope) ? true : null;
        });
    }

    /**
     * The middleware hosts put on their routes (always registered, whether
     * or not Guardian's API routes are on).
     */
    private function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('guardian.two-factor', EnsureTwoFactor::class);
        $router->aliasMiddleware('guardian.session', TrackSession::class);
    }

    private function binds(): bool
    {
        return $this->app->make(Repository::class)->get('guardian.authorization.bind', true) !== false;
    }

    /**
     * SSO and SCIM's migrations (their tables point at Roster's), the
     * upgrade from Roster and its stranded tables warning.
     */
    private function bootWithRoster(): void
    {
        if (! $this->app->make(Integration::class)->rosterInstalled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/roster');

        if ($this->app->runningInConsole()) {
            $this->publishesMigrations([
                __DIR__.'/../database/migrations/roster' => database_path('migrations'),
            ], ['guardian', 'guardian-migrations']);

            $this->commands([UpgradeFromRosterCommand::class]);
        }

        $this->app->booted(fn () => $this->reportStranded());
    }

    /**
     * Once per boot, while Roster's tables still hold rows Guardian's lack
     * (D-029): reported, never thrown, so the app keeps running. SSO
     * enforcement stays off for those connections until the command runs.
     */
    private function reportStranded(): void
    {
        $stranded = $this->stranded();

        if ($stranded !== []) {
            report(StrandedTablesException::forTables($stranded));
        }
    }

    /**
     * Roster's tables still holding rows Guardian's lack. A database that
     * cannot be asked (none configured yet, or down) has nothing to warn
     * about: a warning must never stop the app from booting.
     *
     * @return list<string>
     */
    private function stranded(): array
    {
        if (! $this->app->make(Integration::class)->rosterInstalled()) {
            return [];
        }

        try {
            return $this->app->make(RosterUpgrade::class)->stranded();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * The `guardian` limiter behind `throttle:guardian`: per user, or per IP
     * for guests. A null `guardian.rate_limit.per_minute` turns it off.
     */
    private function registerRateLimiter(): void
    {
        RateLimiter::for('guardian', function (Request $request): Limit {
            $perMinute = $this->app->make(Repository::class)->get('guardian.rate_limit.per_minute');

            if (! is_numeric($perMinute)) {
                return Limit::none();
            }

            $user = $request->user();

            return Limit::perMinute((int) $perMinute)->by($user instanceof Model ? 'user:'.$user->getKey() : 'ip:'.$request->ip());
        });
    }

    /**
     * Guardian's `php artisan about` section: Guardian is always active; SSO
     * and SCIM say what they need when Roster is missing or too old; and
     * Roster tables the upgrade has yet to move. Asked when the command runs.
     */
    private function registerAbout(): void
    {
        AboutCommand::add('Guardian', function (): array {
            $stranded = $this->stranded();

            return ['Status' => 'Active', 'SSO and SCIM' => $this->ssoAndScimStatus()] + ($stranded === [] ? [] : [
                'Upgrade' => 'Data left in '.StrandedTablesException::named($stranded).': run php artisan guardian:upgrade-from-roster',
            ]);
        });
    }

    private function ssoAndScimStatus(): string
    {
        $integration = $this->app->make(Integration::class);

        return match (true) {
            $integration->rosterInstalled() => 'Active',
            $integration->rosterPresent() => 'Inactive: needs a newer refactor-circus/roster',
            default => 'Inactive: install refactor-circus/roster',
        };
    }
}
