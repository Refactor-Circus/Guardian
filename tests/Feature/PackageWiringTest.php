<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

/*
 * Guardian is wired into the suite the way Keen is (criteria 1, 2 and 4).
 * Keen's files are read from its installed copy, so a change there shows up
 * here. Roster's SSO vendor wording is pinned to the Roster commit the move
 * is taken from, because Roster drops those entries once it hands SSO over.
 */

const GUARDIAN_ROOT = __DIR__.'/../..';

const KEEN_ROOT = GUARDIAN_ROOT.'/vendor/refactor-circus/keen';

/** Roster `auth-extension-seams` (PR #11), the source of the moved SSO and SCIM code. */
const ROSTER_PINNED_SHA = '6b1833dae7ed21ba352612a13693d662bc7354cc';

/** Roster's composer.json `suggest` values for the SSO vendors at ROSTER_PINNED_SHA. */
const ROSTER_SSO_VENDOR_SUGGESTIONS = [
    'laravel/socialite' => 'Required for SSO login (^5.31).',
    'socialiteproviders/saml2' => 'Required for SAML SSO connections (^4.10).',
    'firebase/php-jwt' => 'Required for OIDC SSO connections, to verify id_tokens (^7.2).',
    'socialiteproviders/microsoft-azure' => 'Required for Microsoft Entra ID (Azure) SSO connections (^5.2).',
];

/**
 * @return array<string, mixed>
 */
function wiringJson(string $path): array
{
    /** @var array<string, mixed> */
    return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @return array<string, mixed>
 */
function guardianComposer(): array
{
    return wiringJson(GUARDIAN_ROOT.'/composer.json');
}

/**
 * @return array<string, mixed>
 */
function keenComposer(): array
{
    return wiringJson(KEEN_ROOT.'/composer.json');
}

describe('composer.json', function (): void {
    it('names the package', function (): void {
        expect(guardianComposer()['name'])->toBe('refactor-circus/guardian');
    });

    it('requires only php, Laravel and Keystone', function (): void {
        $require = guardianComposer()['require'];

        expect($require)->toHaveKeys(['php', 'laravel/framework', 'refactor-circus/keystone'])
            ->and(array_keys($require))->toHaveCount(3)
            ->and($require['php'])->toBe('^8.5')
            ->and($require['refactor-circus/keystone'])->toBe('dev-main')
            ->and($require['laravel/framework'])->toBe(keenComposer()['require']['laravel/framework']);
    });

    it('suggests Roster, Atrium, laravel/mcp and the four SSO vendors', function (): void {
        $suggest = guardianComposer()['suggest'];

        expect(array_keys($suggest))->toEqualCanonicalizing([
            'refactor-circus/roster',
            'refactor-circus/atrium',
            'laravel/mcp',
            ...array_keys(ROSTER_SSO_VENDOR_SUGGESTIONS),
        ]);
    });

    it('words the SSO vendor suggestions as Roster does at the pinned commit', function (): void {
        $suggest = guardianComposer()['suggest'];

        foreach (ROSTER_SSO_VENDOR_SUGGESTIONS as $package => $wording) {
            expect($suggest[$package])->toBe($wording, $package.' differs from Roster at '.ROSTER_PINNED_SHA);
        }
    });

    it('develops against Roster, Atrium, Keen, Ringside, Standards, the SSO vendors and Keen\'s dev tools', function (): void {
        $requireDev = guardianComposer()['require-dev'];

        foreach (['roster', 'atrium', 'keen', 'ringside', 'standards'] as $package) {
            expect($requireDev['refactor-circus/'.$package] ?? null)->toBe('dev-main', 'refactor-circus/'.$package);
        }

        expect($requireDev)->toHaveKeys(array_keys(ROSTER_SSO_VENDOR_SUGGESTIONS));

        foreach (keenComposer()['require-dev'] as $package => $constraint) {
            if (! str_starts_with($package, 'refactor-circus/')) {
                expect($requireDev[$package] ?? null)->toBe($constraint, $package);
            }
        }
    });

    it('has a VCS repository for every suite package it installs', function (): void {
        $composer = guardianComposer();

        $urls = array_map(
            fn (array $repository): string => $repository['url'],
            array_filter($composer['repositories'], fn (array $repository): bool => $repository['type'] === 'vcs'),
        );

        expect(array_values($urls))->toEqualCanonicalizing(array_map(
            fn (string $repo): string => 'https://github.com/Refactor-Circus/'.$repo.'.git',
            ['Keystone', 'Roster', 'Atrium', 'Keen', 'Ringside', 'Standards'],
        ));

        $suite = array_filter(
            array_keys([...$composer['require'], ...$composer['require-dev']]),
            fn (string $package): bool => str_starts_with($package, 'refactor-circus/'),
        );

        $named = array_map(fn (string $url): string => strtolower(basename($url, '.git')), $urls);

        foreach ($suite as $package) {
            expect($named)->toContain(substr($package, strlen('refactor-circus/')));
        }
    });

    it('refuses Atrium releases older than the screen extensions registry', function (): void {
        $conflict = guardianComposer()['conflict'];

        expect($conflict)->toBe(['refactor-circus/atrium' => '<0.1.0']);
    });

    it('registers the service provider and the Guardian facade alias', function (): void {
        expect(guardianComposer()['extra']['laravel'])->toBe([
            'providers' => ['RefactorCircus\\Guardian\\GuardianServiceProvider'],
            'aliases' => ['Guardian' => 'RefactorCircus\\Guardian\\Facades\\Guardian'],
        ]);
    });
});

describe('local install', function (): void {
    it('has Keen\'s scripts, with Keen\'s local:install and local:update, plus test:browser', function (): void {
        $scripts = guardianComposer()['scripts'];
        $keen = keenComposer()['scripts'];

        // `composer test:browser` runs the moved SSO and SCIM browser tests,
        // outside `composer test` (criterion 29).
        expect($scripts['test:browser'] ?? null)->toBe(['vendor/bin/pest --testsuite=Browser']);
        unset($scripts['test:browser']);

        expect(array_keys($scripts))->toEqualCanonicalizing(array_keys($keen))
            ->and($scripts['local:install'])->toBe($keen['local:install'])
            ->and($scripts['local:update'])->toBe($keen['local:update']);
    });

    it('ships Keen\'s bin/composer-local.php byte for byte', function (): void {
        expect(file_get_contents(GUARDIAN_ROOT.'/bin/composer-local.php'))
            ->toBe(file_get_contents(KEEN_ROOT.'/bin/composer-local.php'));
    });

    it('runs the Arch, Feature and Modes suites, and Browser only when asked', function (): void {
        $phpunit = simplexml_load_file(GUARDIAN_ROOT.'/phpunit.xml.dist');

        expect($phpunit)->not->toBeFalse();

        $suites = array_map(fn (SimpleXMLElement $suite): string => (string) $suite['name'], $phpunit->xpath('//testsuite') ?: []);

        // Browser tests need Playwright: a bare `pest` leaves them out.
        expect($suites)->toBe(['Arch', 'Feature', 'Modes', 'Browser'])
            ->and((string) $phpunit['defaultTestSuite'])->toBe('Arch,Feature,Modes');
    });

    it('keeps the generated local manifest out of git', function (): void {
        $ignored = file(GUARDIAN_ROOT.'/.gitignore', FILE_IGNORE_NEW_LINES);

        expect($ignored)->toContain('composer.local.json', 'composer.local.lock');
    });

    it('checks the Ring Master files in CI', function (): void {
        $workflow = Yaml::parseFile(GUARDIAN_ROOT.'/.github/workflows/tests.yml');

        $runs = array_column($workflow['jobs']['tests']['steps'], 'run');

        expect($runs)->toContain('vendor/bin/ringside check');
    });
});

describe('Ring Master files', function (): void {
    it('has the Ring Master layout', function (string $path): void {
        expect(file_exists(GUARDIAN_ROOT.'/'.$path))->toBeTrue($path.' is missing');
    })->with([
        'ring-master.yml',
        'pint.json',
        'docs/agent/product',
        'docs/agent/specs',
        'docs/agent/standards',
        'docs/agent/standards-feedback.md',
        'docs/agent/product/roadmap.md',
        'AGENTS.md',
        'CLAUDE.md',
    ]);

    it('uses Keen\'s commands block', function (): void {
        expect(Yaml::parseFile(GUARDIAN_ROOT.'/ring-master.yml')['commands'])
            ->toBe(Yaml::parseFile(KEEN_ROOT.'/ring-master.yml')['commands']);
    });

    it('ignores the generated files', function (): void {
        $ignored = file(GUARDIAN_ROOT.'/.gitignore', FILE_IGNORE_NEW_LINES);

        expect($ignored)->toContain(
            '/docs/agent/standards.index.yml',
            '/docs/agent/standards.summary.md',
            '/.claude/skills/ring-master-*/',
            '/.claude/agents/ring-master-*.md',
            '/.agents/skills/ring-master-*/',
            '/.agents/agents/ring-master-*.md',
        );
    });

    it('carries the generated Ring Master block in AGENTS.md', function (): void {
        expect(file_get_contents(GUARDIAN_ROOT.'/AGENTS.md'))
            ->toContain('<!-- ring-master:start -->', '<!-- ring-master:end -->');
    });

    it('describes Guardian\'s role in the suite in CLAUDE.md', function (): void {
        expect(file_get_contents(GUARDIAN_ROOT.'/CLAUDE.md'))
            ->toContain('## Role in the suite', 'refactor-circus/keystone', 'refactor-circus/roster', 'refactor-circus/atrium');
    });

    it('lists SSO and SCIM as done, and MFA, session policies and IP rules as planned', function (): void {
        $roadmap = (string) file_get_contents(GUARDIAN_ROOT.'/docs/agent/product/roadmap.md');

        $done = str($roadmap)->after('## Done')->before('## Planned')->toString();
        $planned = str($roadmap)->after('## Planned')->toString();

        expect($done)->toContain('**SSO**', '**SCIM**')
            ->and($planned)->toContain('**MFA**', '**Session policies**', '**IP rules**')
            ->and($planned)->not->toContain('**SSO**')
            ->and($planned)->not->toContain('**SCIM**');
    });
});
