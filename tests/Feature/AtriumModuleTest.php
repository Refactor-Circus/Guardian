<?php

declare(strict_types=1);

/*
 * Guardian ships its Atrium front-end module: JavaScript only, its sources in
 * `atrium/` (export-ignored), built into the committed `dist/atrium/` and
 * declared in composer.json's `extra.atrium`, which Atrium reads to inject
 * the script and stylesheet into its page. No PHP of Guardian's knows Atrium
 * (RemovedSurfacesTest, ArchTest).
 */

/**
 * @return array<string, mixed>
 */
function atriumModuleManifest(): array
{
    /** @var array<string, mixed> */
    return json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * The SHA-256 of `inputs` under `root`, as Atrium's preset (`hashInputs()`
 * in `@refactor-circus/atrium/vite`) computes it for `build.json`.
 *
 * @param  list<string>  $inputs
 */
function atriumSourcesHash(string $root, array $inputs): string
{
    $files = [];

    $walk = function (string $path) use (&$walk, &$files, $root): void {
        $absolute = $root.'/'.$path;

        if (! file_exists($absolute)) {
            return;
        }

        if (is_dir($absolute)) {
            foreach (scandir($absolute) ?: [] as $entry) {
                if (! in_array($entry, ['node_modules', 'dist', '.DS_Store', '.git'], true) && ! str_starts_with($entry, '.')) {
                    $walk($path.'/'.$entry);
                }
            }

            return;
        }

        $files[] = $path;
    };

    foreach ($inputs as $input) {
        $walk(rtrim($input, '/'));
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_STRING);

    $lines = array_map(
        fn (string $path): string => $path."\0".hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($root.'/'.$path)))."\n",
        $files,
    );

    return hash('sha256', implode('', $lines));
}

it('declares the module in extra.atrium, pointing at the committed build', function (): void {
    $root = dirname(__DIR__, 2);
    /** @var array<string, mixed> $extra */
    $extra = atriumModuleManifest()['extra'];

    expect($extra['atrium'] ?? null)->toBe([
        'key' => 'guardian',
        'scripts' => ['dist/atrium/atrium.js'],
        'styles' => ['dist/atrium/atrium.css'],
    ]);

    foreach (['dist/atrium/atrium.js', 'dist/atrium/atrium.css', 'dist/atrium/build.json'] as $path) {
        expect(is_file($root.'/'.$path))->toBeTrue("{$path} is missing: run `pnpm -C atrium build` and commit dist/atrium.");
    }
});

it('was built from the sources as they are now', function (): void {
    $file = dirname(__DIR__, 2).'/dist/atrium/build.json';
    /** @var array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string} $info */
    $info = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $root = realpath(dirname($file).'/'.$info['root']);

    expect($info['key'])->toBe('guardian')
        ->and($info['shared_api'])->toBe('1.0')
        ->and($root)->toBe(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'atrium')
        ->and(atriumSourcesHash((string) $root, $info['inputs']))->toBe($info['hash'], 'dist/atrium is older than its sources: run `pnpm -C atrium build` and commit the result.');
});

it('ships dist/ and export-ignores the module sources', function (): void {
    $attributes = (string) file_get_contents(dirname(__DIR__, 2).'/.gitattributes');

    expect(preg_match('#^/atrium\s+export-ignore$#m', $attributes))->toBe(1)
        ->and(preg_match('#^/dist\S*\s+export-ignore$#m', $attributes))->toBe(0);
});
