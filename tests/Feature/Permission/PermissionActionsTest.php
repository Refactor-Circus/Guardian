<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Guardian\Domains\Permission\Actions\DeletePermissionAction;
use RefactorCircus\Guardian\Domains\Permission\Actions\ListPermissionsAction;
use RefactorCircus\Guardian\Domains\Permission\Actions\UpdatePermissionAction;
use RefactorCircus\Guardian\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Guardian\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Guardian\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionCreatedActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionCreatingActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionDeletedActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionDeletingActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionsListedActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionsListingActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionUpdatedActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Events\PermissionUpdatingActionEvent;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;

// Criterion 16: the permission actions, moved from Roster.

/**
 * @return array<string, array<int, string>>
 */
function permissionErrors(Closure $call): array
{
    try {
        $call();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

it('creates an app permission with unique, dotted names', function (): void {
    Event::fake([PermissionCreatingActionEvent::class, PermissionCreatedActionEvent::class]);

    $permission = app(CreatePermissionAction::class)->execute(CreatePermissionData::from(['name' => 'invoices.edit', 'description' => 'Edit invoices']));

    expect($permission->system)->toBeFalse()
        ->and($permission->description)->toBe('Edit invoices')
        ->and(permissionErrors(fn () => CreatePermissionData::from(['name' => 'invoices.edit'])))->toHaveKey('name')
        ->and(permissionErrors(fn () => CreatePermissionData::from(['name' => 'Invoices Edit'])))->toHaveKey('name');

    Event::assertDispatched(PermissionCreatingActionEvent::class);
    Event::assertDispatched(PermissionCreatedActionEvent::class);
});

it('renames an app permission and changes its description, keeping the roles that grant it', function (): void {
    Event::fake([PermissionUpdatingActionEvent::class, PermissionUpdatedActionEvent::class]);
    $role = roleGranting('billing', ['invoices.edit']);
    $permission = PermissionModel::query()->where('name', 'invoices.edit')->sole();

    $renamed = app(UpdatePermissionAction::class)->execute($permission, UpdatePermissionData::from(['name' => 'invoices.update']));
    $cleared = app(UpdatePermissionAction::class)->execute($renamed, UpdatePermissionData::from(['description' => null]));

    expect($cleared->name)->toBe('invoices.update')
        ->and($cleared->description)->toBeNull()
        ->and($role->permissions()->pluck('name')->all())->toBe(['invoices.update'])
        ->and(permissionErrors(fn () => app(UpdatePermissionAction::class)->execute($cleared, UpdatePermissionData::from(['name' => 'guardian.roles.view']))))->toHaveKey('name');

    Event::assertDispatchedTimes(PermissionUpdatingActionEvent::class, 2);
    Event::assertDispatchedTimes(PermissionUpdatedActionEvent::class, 2);
});

it('never renames or deletes a system permission, but edits its description', function (): void {
    $system = PermissionModel::query()->where('name', 'guardian.roles.view')->sole();

    expect(permissionErrors(fn () => app(UpdatePermissionAction::class)->execute($system, UpdatePermissionData::from(['name' => 'roles.view']))))->toHaveKey('name')
        ->and(permissionErrors(fn () => app(DeletePermissionAction::class)->execute($system)))->toHaveKey('permission')
        ->and(app(UpdatePermissionAction::class)->execute($system, UpdatePermissionData::from(['description' => 'See roles']))->description)->toBe('See roles')
        ->and(PermissionModel::query()->where('name', 'guardian.roles.view')->exists())->toBeTrue();
});

it('deletes an app permission, and every role stops granting it', function (): void {
    Event::fake([PermissionDeletingActionEvent::class, PermissionDeletedActionEvent::class]);
    $role = roleGranting('billing', ['invoices.edit', 'guardian.sso.view']);

    app(DeletePermissionAction::class)->execute(PermissionModel::query()->where('name', 'invoices.edit')->sole());

    expect(PermissionModel::query()->where('name', 'invoices.edit')->exists())->toBeFalse()
        ->and($role->permissions()->pluck('name')->all())->toBe(['guardian.sso.view']);

    Event::assertDispatched(PermissionDeletingActionEvent::class);
    Event::assertDispatched(PermissionDeletedActionEvent::class);
});

it('forgets remembered answers when permissions change', function (): void {
    config(['guardian.permission_cache.ttl' => 300]);
    $ada = user();
    $permission = app(CreatePermissionAction::class)->execute(CreatePermissionData::from(['name' => 'invoices.edit']));
    assignDirectly($ada, roleGranting('billing', ['invoices.edit']));

    expect(app(Permissions::class)->allows($ada, 'invoices.edit'))->toBeTrue();

    $renamed = app(UpdatePermissionAction::class)->execute($permission, UpdatePermissionData::from(['name' => 'invoices.update']));

    expect(app(Permissions::class)->allows($ada, 'invoices.edit'))->toBeFalse()
        ->and(app(Permissions::class)->allows($ada, 'invoices.update'))->toBeTrue();

    app(DeletePermissionAction::class)->execute($renamed);

    expect(app(Permissions::class)->allows($ada, 'invoices.update'))->toBeFalse();
});

it('lists permissions by name, with a search', function (): void {
    Event::fake([PermissionsListingActionEvent::class, PermissionsListedActionEvent::class]);
    roleGranting('billing', ['invoices.edit', 'invoices_view']);

    $names = fn (array $filters): array => app(ListPermissionsAction::class)->execute(ListPermissionsData::from($filters))->getCollection()->pluck('name')->all();

    expect($names(['search' => 'invoices']))->toBe(['invoices.edit', 'invoices_view'])
        ->and($names([]))->toContain('guardian.roles.view', 'invoices.edit');

    Event::assertDispatched(PermissionsListingActionEvent::class);
    Event::assertDispatched(PermissionsListedActionEvent::class);
});
