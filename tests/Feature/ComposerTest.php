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

it('requires php, Laravel, Keystone ^2.0 and google2fa, and no other suite package', function (): void {
    $require = composerManifest()['require'];

    expect(array_keys($require))->toEqualCanonicalizing(['php', 'laravel/framework', 'refactor-circus/keystone', 'pragmarx/google2fa'])
        ->and($require['php'])->toBe('^8.5')
        ->and($require['refactor-circus/keystone'])->toBe('^2.0');
});

it('develops against Roster and Keen ^2.0, Scramble, Sanctum, Passport and WebAuthn', function (): void {
    $requireDev = composerManifest()['require-dev'];

    expect($requireDev['refactor-circus/roster'] ?? null)->toBe('^2.0')
        ->and($requireDev['refactor-circus/keen'] ?? null)->toBe('^2.0')
        ->and($requireDev)->toHaveKeys(['dedoc/scramble', 'laravel/sanctum', 'laravel/passport', 'web-auth/webauthn-lib']);
});

it('pins every suite package in require and require-dev to ^2.0, bar the dev-main tooling', function (): void {
    $manifest = composerManifest();

    foreach ([...$manifest['require'], ...$manifest['require-dev']] as $package => $constraint) {
        if (str_starts_with($package, 'refactor-circus/') && ! in_array($package, ['refactor-circus/ringside', 'refactor-circus/standards'], true)) {
            expect($constraint)->toBe('^2.0', $package);
        }
    }
});

it('suggests Roster for SSO and SCIM, the token packages, WebAuthn, laravel/mcp and the Socialite packages', function (): void {
    expect(array_keys(composerManifest()['suggest']))->toEqualCanonicalizing([
        'refactor-circus/roster',
        'laravel/sanctum',
        'laravel/passport',
        'web-auth/webauthn-lib',
        'laravel/mcp',
        'laravel/socialite',
        'socialiteproviders/saml2',
        'socialiteproviders/microsoft-azure',
        'firebase/php-jwt',
    ]);
});

it('aliases dev-main to 2.0.x-dev', function (): void {
    expect(composerManifest()['extra']['branch-alias'] ?? null)->toBe(['dev-main' => '2.0.x-dev']);
});

it('describes Guardian as the suite\'s security package with a JSON API and MCP', function (): void {
    expect(composerManifest()['description'])->toContain('security package', 'JSON API', 'MCP');
});
