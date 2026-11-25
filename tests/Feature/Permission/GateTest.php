<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Gate;

/*
 * Criterion 14: with binding on, Guardian answers the Gate for super-admins
 * and the abilities it has permission rows for, and never says no.
 */

it('lets a super-admin through every Gate check', function (): void {
    $boss = user();
    grant($boss, 'super-admin');

    expect(Gate::forUser($boss)->allows('anything-at-all'))->toBeTrue();
});

it('answers a known ability globally or in the first model argument', function (): void {
    $ada = user();
    $folder = folder();
    assignDirectly($ada, roleGranting('viewer', ['guardian.roles.view']), $folder);

    expect(Gate::forUser($ada)->allows('guardian.roles.view', [$folder]))->toBeTrue()
        ->and(Gate::forUser($ada)->allows('guardian.roles.view', ['not a model', $folder]))->toBeTrue()
        ->and(Gate::forUser($ada)->allows('guardian.roles.view'))->toBeFalse()
        ->and(Gate::forUser($ada)->allows('guardian.roles.view', [folder()]))->toBeFalse();
});

it('never answers false: the app\'s own gates still decide', function (): void {
    $ada = user();
    Gate::define('guardian.roles.view', fn (): bool => true);
    Gate::define('reports.view', fn (): bool => true);

    expect(Gate::forUser($ada)->allows('guardian.roles.view'))->toBeTrue()
        ->and(Gate::forUser($ada)->allows('reports.view'))->toBeTrue();
});

it('leaves guests and unknown abilities to the app', function (): void {
    Gate::define('reports.view', fn (): bool => false);

    expect(Gate::forUser(null)->allows('guardian.roles.view'))->toBeFalse()
        ->and(Gate::forUser(user())->allows('reports.view'))->toBeFalse();
});
