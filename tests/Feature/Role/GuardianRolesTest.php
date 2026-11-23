<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Services\GuardianRoles;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;

/*
 * Criterion 12: Guardian as Keystone's role store.
 */

function guardianRoles(): GuardianRoles
{
    return app(GuardianRoles::class);
}

it('is available', function (): void {
    expect(guardianRoles()->available())->toBeTrue();
});

it('knows where each kind of role may be assigned', function (): void {
    $folder = folder();
    $other = folder();
    roleGranting('folder-only', [], (new FolderScope)->getMorphClass());
    roleGranting('own', [], (new FolderScope)->getMorphClass(), owner: $folder);

    expect(guardianRoles()->exists('super-admin'))->toBeTrue()
        ->and(guardianRoles()->exists('super-admin', $folder))->toBeFalse()
        ->and(guardianRoles()->exists('member'))->toBeTrue()
        ->and(guardianRoles()->exists('member', $folder))->toBeTrue()
        ->and(guardianRoles()->exists('folder-only', $folder))->toBeTrue()
        ->and(guardianRoles()->exists('folder-only'))->toBeFalse()
        ->and(guardianRoles()->exists('folder-only', user()))->toBeFalse()
        ->and(guardianRoles()->exists('own', $folder))->toBeTrue()
        ->and(guardianRoles()->exists('own', $other))->toBeFalse()
        ->and(guardianRoles()->exists('nobody'))->toBeFalse();
});

it('assigns idempotently, and refuses a role not assignable there', function (): void {
    $ada = user();
    $folder = folder();

    expect(guardianRoles()->assign($ada, 'member', $folder))->toBeTrue()
        ->and(guardianRoles()->assign($ada, 'member', $folder))->toBeTrue()
        ->and(RoleAssignmentModel::query()->where('user_id', $ada->getKey())->count())->toBe(1)
        ->and(guardianRoles()->assign($ada, 'super-admin', $folder))->toBeFalse()
        ->and(guardianRoles()->assign($ada, 'nobody'))->toBeFalse()
        ->and(guardianRoles()->of($ada, $folder))->toBe(['member']);
});

it('assigns the scope\'s own role before a shared one with the same slug', function (): void {
    $ada = user();
    $folder = folder();
    $own = roleGranting('member', ['guardian.roles.view'], (new FolderScope)->getMorphClass(), owner: $folder);

    guardianRoles()->assign($ada, 'member', $folder);

    expect(RoleAssignmentModel::query()->where('user_id', $ada->getKey())->sole()->role_id)->toBe($own->id)
        ->and(app(GuardianPermissions::class)->allows($ada, 'guardian.roles.view', $folder))->toBeTrue();
});

it('revokes in exactly one scope, or everywhere', function (): void {
    $ada = user();
    $first = folder();
    $second = folder();
    guardianRoles()->assign($ada, 'admin');
    guardianRoles()->assign($ada, 'member', $first);
    guardianRoles()->assign($ada, 'admin', $first);
    guardianRoles()->assign($ada, 'member', $second);

    guardianRoles()->revoke($ada, $first);

    expect(guardianRoles()->of($ada, $first))->toBe([])
        ->and(guardianRoles()->of($ada, $second))->toBe(['member'])
        ->and(guardianRoles()->of($ada))->toBe(['admin']);

    guardianRoles()->revoke($ada);

    expect(guardianRoles()->of($ada))->toBe([])
        ->and(guardianRoles()->of($ada, $second))->toBe(['member']);

    guardianRoles()->revokeEverywhere($ada);

    expect(RoleAssignmentModel::query()->where('user_id', $ada->getKey())->exists())->toBeFalse();
});

it('lists a user\'s roles in a scope, sorted', function (): void {
    $ada = user();
    $folder = folder();
    guardianRoles()->assign($ada, 'member', $folder);
    guardianRoles()->assign($ada, 'admin', $folder);

    expect(guardianRoles()->of($ada, $folder))->toBe(['admin', 'member']);
});

it('names the holders of an ability: global grants, super roles and verified configured super-admins', function (): void {
    config(['guardian.super_admins' => ['root@example.com', 'unverified@example.com']]);
    $viewer = user();
    $scoped = user();
    $boss = user();
    $root = user(['email' => 'root@example.com', 'email_verified_at' => now()]);
    user(['email' => 'unverified@example.com', 'email_verified_at' => null]);
    user();

    assignDirectly($viewer, roleGranting('viewer', ['guardian.roles.view']));
    assignDirectly($scoped, RoleModel::query()->where('slug', 'viewer')->sole(), folder());
    grant($boss, 'super-admin');

    $holders = guardianRoles()->holders('guardian.roles.view');
    sort($holders);
    $expected = [$viewer->getKey(), $boss->getKey(), $root->getKey()];
    sort($expected);

    expect($holders)->toBe($expected);
});
