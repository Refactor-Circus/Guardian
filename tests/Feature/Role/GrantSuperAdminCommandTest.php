<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;

// Criterion 19: `guardian:grant-super-admin`, moved from Roster.

it('makes an existing user a super-admin, matching the email in any case', function (): void {
    $ada = user(['email' => 'ada@example.com']);

    $this->artisan('guardian:grant-super-admin', ['email' => 'ADA@example.com'])
        ->expectsOutputToContain('is now a super-admin')
        ->assertSuccessful();

    expect(app(Roles::class)->of($ada))->toBe(['super-admin'])
        ->and(app(Permissions::class)->isSuperAdmin($ada))->toBeTrue();

    // Running it again changes nothing.
    $this->artisan('guardian:grant-super-admin', ['email' => 'ada@example.com'])->assertSuccessful();

    expect(app(Roles::class)->of($ada))->toBe(['super-admin']);
});

it('fails with a message for an unknown email', function (): void {
    $this->artisan('guardian:grant-super-admin', ['email' => 'nobody@example.com'])
        ->expectsOutputToContain('No user has the email [nobody@example.com].')
        ->assertFailed();
});
