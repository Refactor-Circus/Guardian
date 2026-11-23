<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use RefactorCircus\Guardian\Domains\Token\Contracts\TokenDriver;
use RefactorCircus\Guardian\Domains\Token\Drivers\PassportTokenDriver;
use RefactorCircus\Guardian\Domains\Token\Drivers\SanctumTokenDriver;
use RefactorCircus\Guardian\Domains\Token\Exceptions\TokenDriverException;
use RefactorCircus\Guardian\Domains\Token\Support\TokenPackages;

/**
 * Picks the API token driver from `guardian.tokens.driver` (criterion 45,
 * D-017): null (the default) is no driver, so the token API answers 501;
 * `sanctum` and `passport` select the adapters; any other value is a class
 * implementing `TokenDriver`, resolved from the container. A missing package
 * or an unknown value throws `TokenDriverException` when a driver is first
 * asked for, never while the app boots.
 */
final class TokenManager
{
    public function __construct(
        private readonly Container $container,
        private readonly Repository $config,
        private readonly TokenPackages $packages,
    ) {}

    public function configured(): bool
    {
        $driver = $this->config->get('guardian.tokens.driver');

        return $driver !== null && $driver !== '';
    }

    /**
     * @throws TokenDriverException
     */
    public function driver(): TokenDriver
    {
        $driver = $this->config->get('guardian.tokens.driver');

        return match (true) {
            $driver === null || $driver === '' => throw TokenDriverException::none(),
            $driver === 'sanctum' => $this->adapter(SanctumTokenDriver::class, 'sanctum', 'laravel/sanctum'),
            $driver === 'passport' => $this->adapter(PassportTokenDriver::class, 'passport', 'laravel/passport'),
            is_string($driver) && class_exists($driver) && is_subclass_of($driver, TokenDriver::class) => $this->make($driver),
            default => throw TokenDriverException::unknown(is_scalar($driver) ? (string) $driver : get_debug_type($driver)),
        };
    }

    /**
     * @param  class-string<TokenDriver>  $class
     */
    private function adapter(string $class, string $name, string $package): TokenDriver
    {
        if (! $this->packages->installed($package)) {
            throw TokenDriverException::missingPackage($name, $package);
        }

        return $this->make($class);
    }

    /**
     * @param  class-string<TokenDriver>  $class
     */
    private function make(string $class): TokenDriver
    {
        $driver = $this->container->make($class);

        return $driver instanceof TokenDriver ? $driver : throw TokenDriverException::unknown($class);
    }
}
