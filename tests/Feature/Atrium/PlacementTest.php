<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Atrium\Atrium;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Guardian\Atrium\GuardianPlugin;
use RefactorCircus\Guardian\Atrium\Screens;
use RefactorCircus\Guardian\GuardianServiceProvider;

// Criterion 16: where Guardian's tabs and section go is config alone
// (`guardian.atrium.screens`); a bad entry throws in local and testing, and
// is reported once and skipped elsewhere (D-021).

/**
 * @return list<string>
 */
function tabKeys(string $target): array
{
    return array_map(fn (TabDefinition $tab): string => $tab->key, app(Atrium::class)->screens()->tabs($target));
}

/**
 * @return list<string>
 */
function sectionKeys(string $target, string $slot): array
{
    return array_map(fn (SectionDefinition $section): string => $section->key, app(Atrium::class)->screens()->sections($target, $slot));
}

/**
 * Register Guardian's screens again from a placement, on an empty registry.
 */
function placeGuardian(mixed $placements): void
{
    $registry = app(ScreenExtensions::class);
    $registry->flush();

    Screens::register($registry, $placements);
}

it('registers the defaults on Roster\'s organization and user screens at boot', function (): void {
    expect(tabKeys('roster.organization'))->toBe(['guardian.sso', 'guardian.scim'])
        ->and(sectionKeys('roster.user', 'aside'))->toBe(['guardian.sso-identities']);
});

it('registers the plugin under the guardian key, with no navigation', function (): void {
    $plugin = app(Atrium::class)->plugins()['guardian'] ?? null;

    expect($plugin)->toBeInstanceOf(GuardianPlugin::class)
        ->and($plugin?->navigation())->toBe([]);
});

it('places its screens once per application, however often its provider boots', function (): void {
    (new GuardianServiceProvider($this->app))->boot();

    expect(tabKeys('roster.organization'))->toBe(['guardian.sso', 'guardian.scim'])
        ->and(sectionKeys('roster.user', 'aside'))->toBe(['guardian.sso-identities']);
});

it('registers nothing when the placement is empty', function (): void {
    placeGuardian([]);

    expect(tabKeys('roster.organization'))->toBe([])
        ->and(sectionKeys('roster.user', 'aside'))->toBe([]);
});

it('registers a tab wherever the config moves it', function (): void {
    placeGuardian([
        'roster.team' => ['guardian.sso'],
        'roster.organization' => ['guardian.scim'],
    ]);

    expect(tabKeys('roster.team'))->toBe(['guardian.sso'])
        ->and(tabKeys('roster.organization'))->toBe(['guardian.scim']);
});

it('registers a section in the slot the config names', function (): void {
    placeGuardian(['roster.user' => ['after' => 'guardian.sso-identities']]);

    expect(sectionKeys('roster.user', 'after'))->toBe(['guardian.sso-identities'])
        ->and(sectionKeys('roster.user', 'aside'))->toBe([]);
});

it('throws at boot in testing for a bad entry, naming the target and key', function (mixed $placements, string $message): void {
    expect(fn () => placeGuardian($placements))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'an unknown tab key' => [['roster.organization' => ['guardian.nope']], '[roster.organization] [guardian.nope]'],
    'an unknown section key' => [['roster.user' => ['aside' => 'guardian.nope']], '[roster.user] [guardian.nope]'],
    'a section key as a tab' => [['roster.organization' => ['guardian.sso-identities']], '[roster.organization] [guardian.sso-identities]'],
    'a non-string target' => [[0 => ['guardian.sso']], '[0] [guardian.sso]'],
    'a target Atrium rejects' => [['Not A Screen' => ['guardian.sso']], '[Not A Screen] [guardian.sso]'],
    'a slot Atrium rejects' => [['roster.user' => ['Bad Slot' => 'guardian.sso-identities']], '[roster.user] [guardian.sso-identities]'],
]);

it('throws in local too', function (): void {
    app()['env'] = 'local';

    expect(fn () => placeGuardian(['roster.organization' => ['guardian.nope']]))->toThrow(InvalidArgumentException::class);
});

it('reports a bad entry once in production, skips it and registers the rest', function (): void {
    Exceptions::fake();
    app()['env'] = 'production';

    placeGuardian([
        'roster.organization' => ['guardian.nope', 'guardian.sso', 'guardian.scim'],
        'Not A Screen' => ['guardian.sso'],
        'roster.user' => ['aside' => 'guardian.sso-identities'],
    ]);

    Exceptions::assertReportedCount(2);
    Exceptions::assertReported(fn (InvalidArgumentException $e): bool => str_contains($e->getMessage(), '[roster.organization] [guardian.nope]'));
    Exceptions::assertReported(fn (InvalidArgumentException $e): bool => str_contains($e->getMessage(), '[Not A Screen] [guardian.sso]'));

    expect(tabKeys('roster.organization'))->toBe(['guardian.sso', 'guardian.scim'])
        ->and(sectionKeys('roster.user', 'aside'))->toBe(['guardian.sso-identities']);
});

it('reports a placement that is not a list of screens in production, and registers nothing', function (): void {
    Exceptions::fake();
    app()['env'] = 'production';

    placeGuardian('roster.organization');

    Exceptions::assertReportedCount(1);
    expect(tabKeys('roster.organization'))->toBe([]);
});

it('names Roster\'s screens only in config, docs and tests', function (): void {
    $root = dirname(__DIR__, 3);
    $offenders = [];

    foreach ([...phpFiles($root.'/src'), ...phpFiles($root.'/resources'), ...phpFiles($root.'/database'), ...phpFiles($root.'/lang')] as $file) {
        if (preg_match('/roster\.(organization|user)\b(?![.-])/', (string) file_get_contents($file)) === 1) {
            $offenders[] = substr($file, strlen($root) + 1);
        }
    }

    expect($offenders)->toBe([]);
});
