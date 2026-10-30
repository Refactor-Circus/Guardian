<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RefactorCircus\Guardian\Guardian
 */
class Guardian extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RefactorCircus\Guardian\Guardian::class;
    }
}
