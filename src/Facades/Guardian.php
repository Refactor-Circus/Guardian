<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \RefactorCircus\Guardian\Guardian label(string $class, \Closure $label)
 *
 * @see \RefactorCircus\Guardian\Guardian
 */
class Guardian extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RefactorCircus\Guardian\Guardian::class;
    }
}
