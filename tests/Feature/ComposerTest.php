<?php

declare(strict_types=1);

/*
 * composer.json for v2 (criteria 1 and 2): no Atrium anywhere, no browser
 * plugin, and no scripts for the suites that went with them.
 */

/**
 * @return array<string, mixed>
 */
function composerManifest(): array
{
    /** @var array<string, mixed> */
    return json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
}

it('names refactor-circus/atrium in none of its package lists', function (string $section): void {
    expect(array_keys(composerManifest()[$section] ?? []))->not->toContain('refactor-circus/atrium');
})->with(['require', 'require-dev', 'suggest', 'conflict']);

it('has no Atrium repository', function (): void {
    $urls = array_column(composerManifest()['repositories'] ?? [], 'url');

    expect(array_filter($urls, fn (mixed $url): bool => is_string($url) && stripos($url, 'atrium') !== false))->toBe([]);
});

it('has no without-Atrium or browser test script', function (): void {
    expect(composerManifest()['scripts'])->not->toHaveKeys(['test:without-atrium', 'test:browser']);
});

it('drops the browser test plugin', function (): void {
    expect(composerManifest()['require-dev'])->not->toHaveKey('pestphp/pest-plugin-browser');
});
