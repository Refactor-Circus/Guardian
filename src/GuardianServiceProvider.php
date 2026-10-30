<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian;

use Illuminate\Support\ServiceProvider;
use RefactorCircus\Guardian\Console\Commands\GuardianCommand;

class GuardianServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/guardian.php', 'guardian');

        $this->app->singleton(Guardian::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/guardian.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'guardian');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'guardian');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/guardian.php' => config_path('guardian.php'),
        ], ['guardian', 'guardian-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/guardian'),
        ], ['guardian', 'guardian-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/guardian'),
        ], ['guardian', 'guardian-lang']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['guardian', 'guardian-migrations']);

        $this->commands([
            GuardianCommand::class,
        ]);
    }
}
