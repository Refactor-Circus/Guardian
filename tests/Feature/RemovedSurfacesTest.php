<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
 * Criterion 2: v2 has no UI of its own. The Atrium plugin, its screens,
 * views and config are gone; Atrium v2 (or any front end) reaches Guardian
 * through the JSON API and the generated client.
 */

it('names Atrium nowhere in src/ or config/', function (): void {
    $root = dirname(__DIR__, 2);
    $offenders = [];

    foreach ([...phpFiles($root.'/src'), ...phpFiles($root.'/config')] as $file) {
        if (stripos((string) file_get_contents($file), 'atrium') !== false) {
            $offenders[] = substr($file, strlen($root) + 1);
        }
    }

    expect($offenders)->toBe([]);
});

it('registers no atrium.guardian.* route', function (): void {
    $names = array_filter(
        array_keys(Route::getRoutes()->getRoutesByName()),
        fn (string $name): bool => str_starts_with($name, 'atrium.guardian.'),
    );

    expect($names)->toBe([]);
});

it('has no ui or atrium config', function (): void {
    expect(config('guardian.ui'))->toBeNull()
        ->and(config('guardian.atrium'))->toBeNull();
});

it('registers no guardian view namespace and ships no views', function (): void {
    expect(app('view')->getFinder()->getHints())->not->toHaveKey('guardian')
        ->and(is_dir(dirname(__DIR__, 2).'/resources/views'))->toBeFalse()
        ->and(is_dir(dirname(__DIR__, 2).'/src/Atrium'))->toBeFalse();
});

it('deletes the Atrium-only tests and test cases', function (string $path): void {
    expect(file_exists(dirname(__DIR__, 2).'/'.$path))->toBeFalse($path.' still exists');
})->with([
    'tests/Feature/Atrium',
    'tests/Feature/Ui',
    'tests/Browser',
    'tests/Modes/WithoutAtrium',
    'tests/WithoutAtriumTestCase.php',
    'tests/Feature/StylesTest.php',
    'package.json',
    'package-lock.json',
    '.github/workflows/browser-tests.yml',
]);
