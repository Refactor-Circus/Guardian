<?php

declare(strict_types=1);

// Criterion 8: the tests moved from Roster are recorded, by file and test
// name, against the Roster SHA they were copied from, and none is lost.

/**
 * @return array{source: string, sha: string, files: array<string, array{from: string, tests: array<int, string>}>}
 */
function movedTests(): array
{
    /** @var array{source: string, sha: string, files: array<string, array{from: string, tests: array<int, string>}>} */
    return json_decode((string) file_get_contents(dirname(__DIR__).'/moved-tests.json'), true, 512, JSON_THROW_ON_ERROR);
}

/**
 * The names of the `it()` / `test()` cases a test file declares.
 *
 * @return array<int, string>
 */
function declaredTests(string $file): array
{
    preg_match_all("/^(?:it|test)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/m", (string) file_get_contents($file), $matches);

    return array_map(stripslashes(...), $matches[1]);
}

it('pins the moved tests to a Roster commit, not a branch', function (): void {
    $manifest = movedTests();

    expect($manifest['source'])->toBe('refactor-circus/roster')
        ->and($manifest['sha'])->toMatch('/^[0-9a-f]{40}$/')
        ->and($manifest['files'])->not->toBeEmpty();
});

it('keeps at least as many cases per moved file as the manifest records', function (string $file, array $entry): void {
    $path = dirname(__DIR__, 2).'/'.$file;

    expect($path)->toBeFile();

    $declared = declaredTests($path);

    expect(count($declared))->toBeGreaterThanOrEqual(count($entry['tests']))
        ->and(array_values(array_diff($entry['tests'], $declared)))->toBe([]);
})->with(fn (): array => array_map(
    fn (string $file, array $entry): array => [$file, $entry],
    array_keys(movedTests()['files']),
    movedTests()['files'],
));
