<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use RefactorCircus\Guardian\Domains\Token\Contracts\TokenDriver;
use RefactorCircus\Guardian\Domains\Token\Drivers\PassportTokenDriver;
use RefactorCircus\Guardian\Domains\Token\Drivers\SanctumTokenDriver;
use RefactorCircus\Guardian\Domains\Token\Exceptions\TokenDriverException;
use RefactorCircus\Guardian\Domains\Token\Support\TokenPackages;
use RefactorCircus\Guardian\Domains\Token\TokenManager;
use RefactorCircus\Guardian\Exceptions\GuardianException;
use RefactorCircus\Guardian\Tests\Fixtures\FakeTokenDriver;

// Criterion 45: picking the driver.

it('is a singleton with no driver by default: 501', function (): void {
    expect(app(TokenManager::class))->toBe(app(TokenManager::class))
        ->and(config('guardian.tokens.driver'))->toBeNull()
        ->and(app(TokenManager::class)->configured())->toBeFalse();

    try {
        app(TokenManager::class)->driver();
        $this->fail('No exception');
    } catch (TokenDriverException $e) {
        expect($e)->toBeInstanceOf(GuardianException::class)
            ->and($e->status())->toBe(501)
            ->and($e->getMessage())->toBe('No API token driver is configured.');
    }
});

it('selects the Sanctum and Passport adapters by name', function (): void {
    config()->set('guardian.tokens.driver', 'sanctum');
    expect(app(TokenManager::class)->driver())->toBeInstanceOf(SanctumTokenDriver::class);

    config()->set('guardian.tokens.driver', 'passport');
    expect(app(TokenManager::class)->driver())->toBeInstanceOf(PassportTokenDriver::class);
});

it('resolves a host\'s driver class from the container', function (): void {
    $driver = fakeTokenDriver();

    expect(app(TokenManager::class)->driver())->toBe($driver)
        ->and(app(TokenManager::class)->configured())->toBeTrue();
});

it('throws on first use for an unknown value or a class that is not a driver', function (mixed $value): void {
    config()->set('guardian.tokens.driver', $value);

    expect(fn () => app(TokenManager::class)->driver())->toThrow(TokenDriverException::class);
})->with(['nope', stdClass::class, TokenDriver::class, 42]);

it('throws when the selected package is not installed', function (string $driver, string $package): void {
    app()->instance(TokenPackages::class, new class extends TokenPackages
    {
        public function installed(string $package): bool
        {
            return false;
        }
    });
    config()->set('guardian.tokens.driver', $driver);

    expect(fn () => app(TokenManager::class)->driver())->toThrow(TokenDriverException::class, $package);
})->with([['sanctum', 'laravel/sanctum'], ['passport', 'laravel/passport']]);

it('boots with the driver unset and neither package needed', function (): void {
    expect(class_exists(FakeTokenDriver::class))->toBeTrue()
        ->and(app(TokenPackages::class)->installed('laravel/sanctum'))->toBeTrue()
        ->and(app(TokenPackages::class)->installed('laravel/passport'))->toBeTrue()
        ->and(app(TokenPackages::class)->installed('acme/missing'))->toBeFalse();
});
