<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Guardian\GuardianServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GuardianServiceProvider::class,
        ];
    }
}
