<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Browser;

use RefactorCircus\Guardian\Tests\TestCase;

/**
 * Guardian's Atrium screens in a real browser, with Roster's authorization
 * on, as Roster's BrowserTestCase runs them. Run with `composer test:browser`
 * (Playwright: `npm install`); not part of `composer test`.
 *
 * Browser tests run against a real HTTP server, so Atrium's stylesheet and
 * scripts must exist under the test app's public path. Without them Alpine
 * never boots and every interaction silently does nothing.
 */
abstract class BrowserTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->publishAtriumAssets();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('roster.authorization', true);
    }

    protected function publishAtriumAssets(): void
    {
        $target = public_path('vendor/atrium');

        if (! is_dir($target)) {
            mkdir($target, 0777, true);
        }

        foreach (glob(dirname(__DIR__, 2).'/vendor/refactor-circus/atrium/public/*.{css,js}', GLOB_BRACE) ?: [] as $asset) {
            copy($asset, $target.'/'.basename($asset));
        }
    }
}
