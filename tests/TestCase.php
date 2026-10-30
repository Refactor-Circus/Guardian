<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Atrium\AtriumServiceProvider;
use RefactorCircus\Guardian\GuardianServiceProvider;
use RefactorCircus\Guardian\Upgrade\Handover;
use RefactorCircus\Roster\RosterServiceProvider;
use Workbench\App\Models\User;

/**
 * Guardian as an application installs it: with Roster and Atrium, on an
 * in-memory database migrated per test with foreign keys enforced.
 *
 * Roster here may be the seams branch, which still ships its own SSO and
 * SCIM, or a Roster that has handed them over. The suite runs Guardian as it
 * will run once Roster has handed them over (`Handover` bound complete), with
 * a seams Roster's live routes moved out of the way (criterion 28), and
 * Roster's SSO and SCIM tables as Roster created them at the pinned SHA.
 * tests/Modes/HandoverPending runs Guardian before the handover.
 *
 * The `mysql` CI job runs the `upgrade-mysql` group on MySQL 8: set
 * DB_CONNECTION to use another of Testbench's connections.
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
     * Whether the next application boots with Guardian's MCP web and local
     * servers switched on (`guardian.mcp.*`); both are off as shipped.
     */
    public static bool $mcpServers = false;

    /**
     * Whether anyone signed in may open the Atrium dashboard (its
     * `viewAtrium` gate), as in Roster's suite: Guardian's screens are then
     * answered by their own permission checks alone.
     */
    protected bool $openAtrium = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->openAtrium) {
            Gate::define('viewAtrium', fn (): bool => true);
        }
    }

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
        $app['config']->set('database.default', self::connection());
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('hashing.bcrypt.rounds', 4);
        $app['config']->set('roster.users.model', User::class);
        $app['config']->set('guardian.routes.enabled', self::$apiRoutes);

        if (self::$mcpServers) {
            $app['config']->set('guardian.mcp.web.enabled', true);
            $app['config']->set('guardian.mcp.local.enabled', true);
        }
        // Surface tests cover behaviour; tests/Feature/Authorization turns it
        // back on, as Roster's suite does.
        $app['config']->set('roster.authorization', false);
        // Roster at the seams branch still ships SSO and SCIM: keep its live
        // routes off Guardian's identical URIs (criterion 28).
        $app['config']->set('roster.sso.prefix', 'roster-legacy/sso');
        $app['config']->set('roster.scim.enabled', false);

        $app->instance(Handover::class, new Handover(fn (string $class): bool => false));

        // Roster's role-assignment unique key, named from its default table,
        // is longer than MySQL's 64-character limit: a shorter table name
        // keeps Roster's migrations running in the `mysql` CI job.
        if (self::connection() === 'mysql') {
            $app['config']->set('roster.tables.role_assignments', 'roster_grants');
        }
    }

    /**
     * The database connection the suite runs on: Testbench's in-memory
     * SQLite `testing` connection unless DB_CONNECTION names another.
     */
    protected static function connection(): string
    {
        $connection = getenv('DB_CONNECTION');

        return is_string($connection) && $connection !== '' ? $connection : 'testing';
    }

    /**
     * Guardian's create migrations run last, in a batch of their own, so a
     * test can roll back just them.
     *
     * The upgrade migration is left out here: with the handover complete it
     * would rename the empty legacy SSO and SCIM tables (tests/Fixtures).
     * tests/Feature/Upgrade runs it on the states it handles, and
     * tests/Modes/HandoverPending runs every migration.
     */
    protected function defineDatabaseMigrations(): void
    {
        // A server database outlives the test: start each one empty.
        if (self::connection() !== 'testing') {
            Artisan::call('db:wipe', ['--force' => true]);
        }

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/refactor-circus/roster/database/migrations');
        // Roster's SSO and SCIM tables as Roster created them at the pinned
        // SHA, so the upgrade has something to move whichever Roster is
        // installed: one that still ships SSO and SCIM has made them already.
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/roster-sso-scim');
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/refactor-circus/atrium/database/migrations');
        $this->loadMigrationsFrom(['--path' => $this->guardianMigrations()]);
    }

    /**
     * @return list<string>
     */
    protected function guardianMigrations(): array
    {
        return array_values(array_filter(
            (array) glob(dirname(__DIR__).'/database/migrations/*.php'),
            fn (mixed $path): bool => is_string($path) && ! str_ends_with($path, '_upgrade_guardian_from_roster.php'),
        ));
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
