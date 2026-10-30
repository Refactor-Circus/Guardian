<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests;

use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Atrium\AtriumServiceProvider;
use RefactorCircus\Guardian\GuardianServiceProvider;
use RefactorCircus\Roster\RosterServiceProvider;
use Workbench\App\Models\User;

/**
 * Guardian as an application installs it: with Roster and Atrium, on an
 * in-memory database migrated per test with foreign keys enforced.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Whether the next application boots with Guardian's JSON API mounted.
     * Surface tests need it; a test that proves the default (off) sets this
     * to false and refreshes the application.
     */
    public static bool $apiRoutes = true;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            RosterServiceProvider::class,
            GuardianServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('hashing.bcrypt.rounds', 4);
        $app['config']->set('roster.users.model', User::class);
        $app['config']->set('guardian.routes.enabled', self::$apiRoutes);
        // Surface tests cover behaviour; tests/Feature/Authorization turns it
        // back on, as Roster's suite does.
        $app['config']->set('roster.authorization', false);
        // Roster at the seams branch still ships SSO and SCIM: keep its live
        // routes off Guardian's identical URIs (criterion 28).
        $app['config']->set('roster.sso.prefix', 'roster-legacy/sso');
        $app['config']->set('roster.scim.enabled', false);
    }

    /**
     * Guardian's migrations run last, in a batch of their own, so a test can
     * roll back just them.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/refactor-circus/roster/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/refactor-circus/atrium/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }

    /**
     * The in-memory database goes with the application, so it is not rolled
     * back first: Guardian's down() would refuse the rows a test left.
     */
    protected function tearDown(): void
    {
        $this->cachedTestMigratorProcessors = [];

        parent::tearDown();
    }
}
