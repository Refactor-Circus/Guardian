<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests\Modes\SsoUnavailable;

use RefactorCircus\Guardian\Domains\Sso\Services\Sso;
use RefactorCircus\Guardian\Tests\TestCase;

/**
 * Guardian as installed without the optional SSO packages.
 */
abstract class SsoUnavailableTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->bind(Sso::class, fn ($app) => new class($app->make('request')) extends Sso
        {
            public function available(?string $protocol = null): bool
            {
                return false;
            }
        });
    }
}
