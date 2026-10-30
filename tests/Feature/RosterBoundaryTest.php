<?php

declare(strict_types=1);

// Criterion 9 (D-024, D-036): Guardian reaches Roster only through its seams
// and public domain API, never its @internal members or its surfaces.

/**
 * The Roster namespaces Guardian may not use: Roster's own surfaces and the
 * support its domains share among themselves.
 */
const DENIED_ROSTER_NAMESPACES = '/RefactorCircus\\\\Roster\\\\(Atrium|Http|Mcp|Support\\\\Concerns|Support\\\\Policies|Domains\\\\[A-Za-z]+\\\\(Http|Mcp))\\\\/';

arch('Guardian uses none of Roster\'s surfaces or shared support')
    ->expect('RefactorCircus\Guardian')
    ->not->toUse([
        'RefactorCircus\Roster\Atrium',
        'RefactorCircus\Roster\Http',
        'RefactorCircus\Roster\Mcp',
        'RefactorCircus\Roster\Support\Concerns',
        'RefactorCircus\Roster\Support\Policies',
    ]);

it('names no class from a Roster domain\'s Http or Mcp namespace', function (): void {
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
