<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests;

use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Server\McpServiceProvider;
use RefactorCircus\Guardian\GuardianServiceProvider;
use RefactorCircus\Guardian\Support\Integration;
use RefactorCircus\Roster\RosterServiceProvider;

/**
 * Guardian with Roster and without Atrium (criterion 30, D-020).
 *
 * Atrium's providers are not registered, Roster skips its own Atrium seam,
 * and the bound Integration reports Roster present and Atrium absent, so this
 * runs the same inside `composer test`, where Atrium is a dev dependency, and
 * in the `without-atrium` CI job, where it is uninstalled. It names no Atrium
 * class.
 */
abstract class WithoutAtriumTestCase extends TestCase
{
    protected function setUp(): void
    {
        // Before the application exists, so RosterServiceProvider::register() sees it.
        RosterServiceProvider::$withoutAtrium = true;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            RosterServiceProvider::$withoutAtrium = false;
        }
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            RosterServiceProvider::class,
            GuardianServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->instance(Integration::class, new Integration(
            fn (string $class): bool => ! str_starts_with($class, 'RefactorCircus\\Atrium\\') && class_exists($class),
        ));
    }

    /**
     * Laravel's, Roster's and Guardian's migrations: no Atrium.
     */
    protected function defineDatabaseMigrations(): void
    {
        if (self::connection() !== 'testing') {
            Artisan::call('db:wipe', ['--force' => true]);
        }

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/refactor-circus/roster/database/migrations');
        $this->loadMigrationsFrom(['--path' => $this->guardianMigrations()]);
    }
}
