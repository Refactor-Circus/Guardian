<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

/*
 * Criterion 22, leftover config (D-030): Guardian never reads
 * `roster.sso.*` / `roster.scim.*`. The upgrade lists each value still set
 * that differs from Guardian's, with the key to move it to, and changes
 * nothing at runtime.
 */

beforeEach(function (): void {
    seedRosterPermissions();
});

it('lists differing roster.sso and roster.scim values with the guardian key to move them to', function (): void {
    config()->set('roster.scim.enabled', false);
    config()->set('roster.sso.prefix', 'login/sso');
    config()->set('roster.scim.bulk.max_operations', 10);

    $report = runUpgrade();

    expect($report->leftovers)->toMatchArray([
        'roster.scim.enabled' => 'guardian.scim.enabled',
        'roster.sso.prefix' => 'guardian.sso.prefix',
        'roster.scim.bulk.max_operations' => 'guardian.scim.bulk.max_operations',
    ])
        ->and(implode("\n", $report->lines()))->toContain('Config [roster.sso.prefix] is set, but Guardian never reads it: move the value to [guardian.sso.prefix].')
        ->and(config('guardian.scim.enabled'))->toBeTrue()
        ->and(config('guardian.sso.prefix'))->toBe('roster/sso')
        ->and(config('guardian.scim.bulk.max_operations'))->toBe(100);
});

it('leaves out values equal to Guardian\'s', function (): void {
    config()->set('roster.sso', ['redirect' => '/', 'prefix' => 'roster/sso', 'middleware' => ['web']]);
    config()->set('roster.scim', null);

    expect(runUpgrade()->leftovers)->toBe([]);
});
