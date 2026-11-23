<?php

declare(strict_types=1);

// Criterion 9 (D-024, D-036): Guardian reaches Roster only through its seams
// and public domain API, never its @internal members or its surfaces.

/**
 * The Roster namespaces Guardian may not use: Roster's own surfaces (Atrium,
 * HTTP, MCP, a domain's Http, Mcp and Resources), the helpers its domains
 * share among themselves (a domain's Concerns), and every `Support` class
 * but the four public ones criterion 9 allows.
 */
const DENIED_ROSTER_NAMESPACES = '/RefactorCircus\\\\Roster\\\\(?:(?:Atrium|Http|Mcp|Domains\\\\[A-Za-z]+\\\\(?:Http|Mcp|Concerns|Resources))\\\\|Support\\\\(?!(?:Users|Statuses|UserKey|Tables)\b))/';

arch('Guardian uses none of Roster\'s surfaces or shared support')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse([
        'RefactorCircus\Roster\Atrium',
        'RefactorCircus\Roster\Http',
        'RefactorCircus\Roster\Mcp',
        'RefactorCircus\Roster\Support\Concerns',
        'RefactorCircus\Roster\Support\Policies',
        'RefactorCircus\Roster\Support\CaseInsensitive',
        'RefactorCircus\Roster\Domains\Organization\Concerns',
        'RefactorCircus\Roster\Domains\User\Resources',
    ]);

it('denies Roster\'s surfaces, domain concerns and resources, and its non-public support', function (string $reference): void {
    expect(preg_match(DENIED_ROSTER_NAMESPACES, "use {$reference};"))->toBe(1);
})->with([
    'RefactorCircus\Roster\Atrium\ScreenAccess',
    'RefactorCircus\Roster\Http\Request',
    'RefactorCircus\Roster\Mcp\Request',
    'RefactorCircus\Roster\Domains\User\Http\Requests\Request',
    'RefactorCircus\Roster\Domains\User\Mcp\Requests\Request',
    'RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships',
    'RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource',
    'RefactorCircus\Roster\Support\CaseInsensitive',
    'RefactorCircus\Roster\Support\Concerns\AuthorizesWithRoster',
    'RefactorCircus\Roster\Support\Policies\Policy',
    'RefactorCircus\Roster\Support\Scopes',
]);

it('allows Roster\'s seams and public domain API', function (string $reference): void {
    expect(preg_match(DENIED_ROSTER_NAMESPACES, "use {$reference};"))->toBe(0);
})->with([
    'RefactorCircus\Roster\Roster',
    'RefactorCircus\Roster\Contracts\SignInRequirement',
    'RefactorCircus\Roster\Data\ExternalSignIn',
    'RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel',
    'RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction',
    'RefactorCircus\Roster\Domains\Organization\Services\MembershipSources',
    'RefactorCircus\Roster\Domains\User\Data\CreateUserData',
    'RefactorCircus\Roster\Support\Users',
    'RefactorCircus\Roster\Support\Statuses',
    'RefactorCircus\Roster\Support\UserKey',
    'RefactorCircus\Roster\Support\Tables',
]);

it('names no denied Roster class', function (): void {
    $root = dirname(__DIR__, 2);
    $offenders = [];

    foreach ([...phpFiles($root.'/src'), ...phpFiles($root.'/database')] as $file) {
        if (preg_match(DENIED_ROSTER_NAMESPACES, (string) file_get_contents($file)) === 1) {
            $offenders[] = substr($file, strlen($root) + 1);
        }
    }

    expect($offenders)->toBe([]);
});

it('never calls Roster\'s @internal registration methods', function (): void {
    $root = dirname(__DIR__, 2);
    $offenders = [];

    foreach (phpFiles($root.'/src') as $file) {
        if (preg_match('/\b(rosterResource|rosterPermission)\b/', (string) file_get_contents($file)) === 1) {
            $offenders[] = substr($file, strlen($root) + 1);
        }
    }

    expect($offenders)->toBe([]);
});

/**
 * Every Roster class Guardian's src/ and database/ name, written as code
 * (`Roster\X`) or as a string (`'Roster\\X'`), by file.
 *
 * @return array<string, list<string>>
 */
function rosterReferences(): array
{
    $root = dirname(__DIR__, 2);
    $references = [];

    foreach ([...phpFiles($root.'/src'), ...phpFiles($root.'/database')] as $file) {
        $source = str_replace('\\\\', '\\', (string) file_get_contents($file));

        if (preg_match_all('/RefactorCircus\\\\Roster\\\\[A-Za-z\\\\]*[A-Za-z]/', $source, $matches) > 0) {
            $references[substr($file, strlen($root) + 1)] = array_values(array_unique($matches[0]));
        }
    }

    return $references;
}

// Criterion 4: the existing reference is kept, not widened.
it('names only the allow-listed Roster classes', function (): void {
    /** @var list<string> $allowed */
    $allowed = require dirname(__DIR__).'/Fixtures/roster-references.php';
    $offenders = [];

    foreach (rosterReferences() as $file => $classes) {
        foreach (array_diff($classes, $allowed) as $class) {
            $offenders[] = $file.': '.$class;
        }
    }

    expect($offenders)->toBe([]);
});

it('keeps Roster\'s Role and Permission domains off the allow-list and out of src/', function (): void {
    /** @var list<string> $allowed */
    $allowed = require dirname(__DIR__).'/Fixtures/roster-references.php';
    $named = array_merge(...array_values(rosterReferences()));

    $isRbac = fn (string $class): bool => str_starts_with($class, 'RefactorCircus\\Roster\\Domains\\Role\\')
        || str_starts_with($class, 'RefactorCircus\\Roster\\Domains\\Permission\\');

    expect(array_values(array_filter($allowed, $isRbac)))->toBe([])
        ->and(array_values(array_filter($named, $isRbac)))->toBe([]);
});
