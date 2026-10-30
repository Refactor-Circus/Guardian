<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Guardian\Domains\DomainServiceProvider;
use RefactorCircus\Guardian\Domains\Scim\ScimServiceProvider;
use RefactorCircus\Guardian\Domains\Sso\SsoServiceProvider;
use RefactorCircus\Guardian\GuardianServiceProvider;
use RefactorCircus\Guardian\Mcp\GuardianServer;
use RefactorCircus\Guardian\Support\Integration;
use RefactorCircus\Keystone\Packages\PackageRegistry;

/*
 * Without Roster, Guardian is inert (criterion 7, D-023, D-032): it boots,
 * says why in `php artisan about`, and registers nothing else. These tests
 * also run in the `standalone` CI job, with Roster, Atrium and Keen
 * uninstalled, so they name none of their classes.
 */

/**
 * Guardian's section of `php artisan about`.
 *
 * @return array<string, mixed>
 */
function standaloneAbout(): array
{
    Artisan::call('about', ['--only' => 'guardian', '--json' => true]);

    /** @var array<string, array<string, mixed>> $about */
    $about = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    return $about['guardian'] ?? [];
}

/**
 * Asserts Guardian registered nothing beyond its config, translations,
 * package entry, rate limiter and about section.
 */
function assertGuardianRegisteredNothing(): void
{
    $routes = array_filter(
        array_keys(Route::getRoutes()->getRoutesByName()),
        fn (string $name): bool => str_starts_with($name, 'guardian.') || str_starts_with($name, 'atrium.guardian.'),
    );

    expect($routes)->toBe([])
        ->and(app()->getProviders(DomainServiceProvider::class))->toBe([])
        ->and(app()->getProviders(SsoServiceProvider::class))->toBe([])
        ->and(app()->getProviders(ScimServiceProvider::class))->toBe([])
        ->and((new GuardianServer(new FakeTransporter))->createContext()->tools())->toHaveCount(0)
        ->and(ServiceProvider::pathsToPublish(GuardianServiceProvider::class, 'guardian-migrations'))->toBe([])
        ->and(array_filter(
            app('migrator')->paths(),
            fn (string $path): bool => realpath($path) === realpath(dirname(__DIR__, 3).'/database/migrations'),
        ))->toBe([])
        ->and(array_filter(
            array_keys(Gate::policies()),
            fn (string $model): bool => str_starts_with($model, 'RefactorCircus\\Guardian\\'),
        ))->toBe([])
        ->and(array_filter(
            array_keys(Artisan::all()),
            fn (string $command): bool => str_starts_with($command, 'guardian:'),
        ))->toBe([]);

    $atrium = 'RefactorCircus\\Atrium\\Atrium';

    if (! class_exists($atrium)) {
        return;
    }

    $registrations = app($atrium)->screens()->snapshot();
    $keys = [];

    array_walk_recursive($registrations, function (mixed $value) use (&$keys): void {
        if (is_object($value) && property_exists($value, 'key')) {
            $keys[] = $value->key;
        }
    });

    expect(app($atrium)->pluginRegistry()->has('guardian'))->toBeFalse()
        ->and(array_filter($keys, fn (mixed $key): bool => is_string($key) && str_starts_with($key, 'guardian.')))->toBe([]);
}

it('sees neither Roster nor Atrium', function (): void {
    expect(app(Integration::class)->rosterInstalled())->toBeFalse()
        ->and(app(Integration::class)->rosterPresent())->toBeFalse()
        ->and(app(Integration::class)->atriumInstalled())->toBeFalse();
});

it('says in php artisan about that Roster is needed', function (): void {
    expect(standaloneAbout())->toBe(['status' => 'Inactive: install refactor-circus/roster']);
});

it('registers nothing but its config, translations and package entry', function (): void {
    assertGuardianRegisteredNothing();
});

it('still boots its config, translations, package entry and rate limiter', function (): void {
    expect(config('guardian.sso.prefix'))->toBe('roster/sso')
        ->and(config('guardian.scim.middleware'))->toBe(['api', 'throttle:guardian'])
        ->and(__('guardian::guardian.sso'))->toBe('Single sign-on')
        ->and(app(PackageRegistry::class)->has('guardian'))->toBeTrue()
        ->and(RateLimiter::limiter('guardian'))->not->toBeNull();
});

describe('a Roster without the seams Guardian uses', function (): void {
    it('falls back to the inactive path, saying a newer Roster is needed', function (Integration $integration): void {
        $this->bootWith($integration);

        expect(app(Integration::class)->rosterInstalled())->toBeFalse()
            ->and(app(Integration::class)->rosterPresent())->toBeTrue()
            ->and(standaloneAbout())->toBe(['status' => 'Inactive: needs a newer refactor-circus/roster']);

        assertGuardianRegisteredNothing();
    })->with([
        // Roster's class is there, but neither requireSignIn() nor provisionedStatus().
        'without the seam methods' => fn (): Integration => new Integration(
            fn (string $class): bool => true,
            fn (string $class, string $method): bool => false,
        ),
        // Roster and its methods are there, but not the seam classes.
        'without the seam classes' => fn (): Integration => new Integration(
            fn (string $class): bool => $class === 'RefactorCircus\\Roster\\Roster'
                || $class === 'RefactorCircus\\Atrium\\Domains\\Screens\\Services\\ScreenExtensions',
            fn (string $class, string $method): bool => true,
        ),
    ]);
});
