<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Guardian\GuardianServiceProvider;
use RefactorCircus\Guardian\Support\Integration;

/**
 * Guardian without Roster (D-023, D-032).
 *
 * The bound Integration reports Roster missing, so this runs the same inside
 * `composer test`, where it is a dev dependency, and in the `standalone` CI
 * job, where it is uninstalled. It names no Roster or Keen class.
 */
abstract class StandaloneTestCase extends Orchestra
{
    /**
     * The Integration the next boot binds, in place of the default one.
     */
    private static ?Integration $integration = null;

    /**
     * Publish paths are static, so forget what earlier suites' boots left:
     * this suite asserts Guardian publishes no migrations of its own.
     */
    protected function setUp(): void
    {
        self::forgetPublishedPaths();

        parent::setUp();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [McpServiceProvider::class, GuardianServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
        // Mounted if anything registered them, so their absence means something.
        $app['config']->set('guardian.routes.enabled', true);

        $app->instance(Integration::class, self::$integration ?? new Integration(fn (string $class): bool => false));
    }

    /**
     * Boot the application again with another Integration bound, for what
     * Guardian decides only at boot.
     */
    protected function bootWith(Integration $integration): void
    {
        self::$integration = $integration;
        self::forgetPublishedPaths();

        try {
            $this->refreshApplication();
        } finally {
            self::$integration = null;

            Container::setInstance($this->app);
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->app);
        }
    }

    private static function forgetPublishedPaths(): void
    {
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];
    }
}
