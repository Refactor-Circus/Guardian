<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Upgrade\Handover;

/*
 * Criterion 31: with the handover complete (the suite's default), Guardian
 * serves the SSO web routes and the SCIM server routes. tests/Modes/
 * HandoverPending proves the other side.
 */

it('loads the SSO web and SCIM server routes once the handover is complete', function (): void {
    expect(app(Handover::class)->complete())->toBeTrue()
        ->and(Route::has('guardian.sso.start'))->toBeTrue()
        ->and(Route::has('guardian.sso.callback'))->toBeTrue()
        ->and(Route::has('guardian.scim.users.index'))->toBeTrue();
});

it('asks Roster\'s SSO connection model, by name, whether the handover is complete', function (): void {
    $asked = [];

    $pending = new Handover(function (string $class) use (&$asked): bool {
        $asked[] = $class;

        return true;
    });

    expect($pending->complete())->toBeFalse()
        ->and($asked)->toBe(['RefactorCircus\\Roster\\Domains\\Sso\\Models\\SsoConnectionModel'])
        ->and((new Handover(fn (string $class): bool => false))->complete())->toBeTrue();
});

it('finds the real class with the default check', function (): void {
    // The seams-branch Roster installed here still ships the model.
    expect((new Handover)->complete())->toBeFalse();
});
