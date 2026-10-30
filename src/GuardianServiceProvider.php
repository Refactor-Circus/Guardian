<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use RefactorCircus\Guardian\Domains\DomainServiceProvider;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Support\Integration;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Support\PackageServiceProvider;

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
     * not, what to install. Asked when the command runs.
     */
    private function registerAbout(): void
    {
        AboutCommand::add('Guardian', fn (): array => ['Status' => $this->status()]);
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
