<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Domains\Token\Exceptions;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Guardian\Exceptions\GuardianException;
use Symfony\Component\HttpFoundation\Response;

/**
 * No API token driver is configured (501), or `guardian.tokens.driver`
 * names one that cannot work here (500): an unknown value, a class that is
 * not a `TokenDriver`, or a driver whose package is not installed.
 */
final class TokenDriverException extends GuardianException
{
    public static function none(): self
    {
        $exception = new self('No API token driver is configured.');
        $exception->status = 501;

        return $exception;
    }

    public static function unknown(string $driver): self
    {
        $exception = new self("The API token driver [{$driver}] is not 'sanctum', 'passport' or a class implementing TokenDriver.");
        $exception->status = 500;

        return $exception;
    }

    public static function missingPackage(string $driver, string $package): self
    {
        $exception = new self("The [{$driver}] API token driver needs {$package}: composer require {$package}.");
        $exception->status = 500;

        return $exception;
    }

    /**
     * The user model cannot hold the driver's tokens.
     */
    public static function unsupportedUser(string $driver, string $requirement): self
    {
        $exception = new self("The [{$driver}] API token driver needs a user model with {$requirement}.");
        $exception->status = 500;

        return $exception;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): Response
    {
        return new JsonResponse(['message' => $this->getMessage()], $this->status);
    }
}
