<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Services\GuardianRoles;
use RefactorCircus\Guardian\Tests\TestCase;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Keystone\Authorization\NullRoles;
use Workbench\App\Models\User;

/*
 * Criterion 9: Guardian binds Keystone's contract, per request, unless
 * `guardian.authorization.bind` is off.
 */

afterEach(function (): void {
    TestCase::$bindAuthorization = true;
});

it('binds Permissions and Roles to Guardian\'s, scoped', function (): void {
    expect(app(Roles::class))->toBeInstanceOf(GuardianRoles::class)
        ->and(app(Roles::class))->toBe(app(Roles::class))
        ->and(app(GuardianPermissions::class))->toBe(app(GuardianPermissions::class))
        ->and(app()->isShared(Permissions::class))->toBeTrue()
        ->and(app(Permissions::class)->abilities(null))->toBe([]);

    $before = app(GuardianPermissions::class);
    app()->forgetScopedInstances();

    expect(app(GuardianPermissions::class))->not->toBe($before);
});

it('keeps Keystone\'s defaults with guardian.authorization.bind off', function (): void {
    TestCase::$bindAuthorization = false;
    $this->refreshApplication();

    expect(app(Roles::class))->toBeInstanceOf(NullRoles::class)
        ->and(app(Permissions::class)->abilities(new User))->toBeNull();
});

it('declares the binding switch on by default', function (): void {
    expect(config('guardian.authorization.bind'))->toBeTrue();
});
