<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use RefactorCircus\Guardian\Atrium\GuardianPlugin;
use RefactorCircus\Guardian\Console\Commands\UpgradeFromRosterCommand;
use RefactorCircus\Guardian\Domains\DomainServiceProvider;
use RefactorCircus\Guardian\Exceptions\StrandedTablesException;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Support\Integration;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Guardian\Upgrade\RosterUpgrade;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Support\PackageServiceProvider;
use Throwable;

/**
 * Guardian's package provider.
 *
 * Guardian works on refactor-circus/roster's users and organizations, so
 * everything past its config, translations, package entry, rate limiter and
 * `about` section waits on `Integration::rosterInstalled()`: without a Roster
 * that has the seams Guardian uses, it registers nothing else (D-023, D-032).
 * Whether Roster is there is asked once the application boots, not while
 * providers register, so a bound Integration (tests, or an app) is the one
 * asked.
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

        // Before any provider boots, so the domain providers boot with the rest.
        $this->app->booting(function (): void {
            if ($this->app->make(Integration::class)->rosterInstalled()) {
                $this->app->register(DomainServiceProvider::class);
            }
        });
    }

    public function boot(): void
    {
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
        }

        $this->bootWithRoster();
    }

    /**
     * Everything that works on Roster's users and organizations.
     */
    private function bootWithRoster(): void
    {
        if (! $this->app->make(Integration::class)->rosterInstalled()) {
            return;
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

            $this->commands([UpgradeFromRosterCommand::class]);
        }

        $this->bootWithAtrium();

        $this->app->booted(fn () => $this->reportStranded());
    }

    /**
     * Guardian's Atrium plugin, its views and its tabs and sections on other
     * packages' screens, only while Roster and Atrium (with its screen
     * extensions registry) are installed and `guardian.ui.enabled` is on
     * (D-038). Only
     * src/Atrium names Atrium's classes, so nothing here loads them first.
     */
    private function bootWithAtrium(): void
    {
        $integration = $this->app->make(Integration::class);

        if (! $integration->rosterInstalled() || ! $integration->atriumInstalled() || config('guardian.ui.enabled') !== true) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'guardian');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/guardian'),
            ], ['guardian', 'guardian-views']);
        }

        $this->registerAtriumPlugin(GuardianPlugin::class);
        GuardianPlugin::registerScreens();
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
     * Guardian's `php artisan about` section: whether it is active, and if
     * not, what to install; and Roster tables the upgrade has yet to move.
     * Asked when the command runs.
     */
    private function registerAbout(): void
    {
        AboutCommand::add('Guardian', function (): array {
            $stranded = $this->stranded();

            return ['Status' => $this->status()] + ($stranded === [] ? [] : [
                'Upgrade' => 'Data left in '.StrandedTablesException::named($stranded).': run php artisan guardian:upgrade-from-roster',
            ]);
        });
    }

    private function status(): string
    {
        $integration = $this->app->make(Integration::class);

        return match (true) {
            $integration->rosterInstalled() => 'Active',
            $integration->rosterPresent() => 'Inactive: needs a newer refactor-circus/roster',
            default => 'Inactive: install refactor-circus/roster',
        };
    }
}
