<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Tests;

use RefactorCircus\Guardian\Support\Tables;

/**
 * Every Guardian table renamed through `guardian.tables` before migrating.
 */
abstract class RenamedTablesTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        foreach (array_keys(Tables::DEFAULTS) as $key) {
            $app['config']->set('guardian.tables.'.$key, 'acme_'.$key);
        }
    }
}
