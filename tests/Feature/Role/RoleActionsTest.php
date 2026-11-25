<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Guardian\Domains\Role\Actions\DeleteRoleAction;
use RefactorCircus\Guardian\Domains\Role\Actions\ListRolesAction;
use RefactorCircus\Guardian\Domains\Role\Actions\ShowRoleAction;
use RefactorCircus\Guardian\Domains\Role\Actions\UpdateRoleAction;
use RefactorCircus\Guardian\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Guardian\Domains\Role\Data\ListRolesData;
use RefactorCircus\Guardian\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Guardian\Domains\Role\Events\RoleCreatedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleCreatingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleDeletedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleDeletingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleShowingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleShownActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RolesListedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RolesListingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleUpdatedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleUpdatingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;

// Criteria 15 and 16: the role actions, moved from Roster onto scope morphs.

/**
 * @param  array<string, mixed>  $input
 */
function createRole(array $input, mixed $actor = null): RoleModel
{
    return app(CreateRoleAction::class)->execute(CreateRoleData::from($input), $actor);
}

/**
 * The validation errors a call raises, by field.
 *
 * @return array<string, array<int, string>>
 */
function roleErrors(Closure $call): array
{
    try {
        $call();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

it('creates a shared role, with a slug from its name and the permissions it grants', function (): void {
    Event::fake([RoleCreatingActionEvent::class, RoleCreatedActionEvent::class]);

    $role = createRole(['name' => 'Release Managers', 'permissions' => ['guardian.roles.view', 'guardian.sso.view']]);

    expect($role->slug)->toBe('release-managers')
        ->and($role->scope)->toBe(RoleModel::ANY)
        ->and($role->owner_type)->toBeNull()
        ->and($role->system)->toBeFalse()
        ->and($role->permissions->pluck('name')->sort()->values()->all())->toBe(['guardian.roles.view', 'guardian.sso.view']);

    Event::assertDispatched(RoleCreatingActionEvent::class);
    Event::assertDispatched(RoleCreatedActionEvent::class, fn (RoleCreatedActionEvent $event): bool => $event->role->is($role));
});

it('creates a scope\'s own role, assignable only there', function (): void {
    $folder = folder();

    $role = createRole(['name' => 'Editors', 'scope_type' => FolderScope::class, 'scope_id' => $folder->id, 'scope' => FolderScope::class]);

    expect($role->owner_type)->toBe($folder->getMorphClass())
        ->and($role->owner_id)->toBe($folder->id)
        ->and($role->scope)->toBe($folder->getMorphClass());

    // The same slug may live once per owner.
    expect(createRole(['name' => 'Editors'])->owner_id)->toBeNull()
        ->and(roleErrors(fn () => createRole(['name' => 'Editors', 'scope_type' => FolderScope::class, 'scope_id' => $folder->id])))->toHaveKey('slug');
});

it('refuses a global role owned by a scope, a scope of another type, an unknown scope and a taken slug', function (): void {
    $folder = folder();
    $owned = ['scope_type' => FolderScope::class, 'scope_id' => $folder->id];

    expect(roleErrors(fn () => createRole(['name' => 'X', 'scope' => 'global'] + $owned)))->toHaveKey('scope')
        ->and(roleErrors(fn () => createRole(['name' => 'X', 'scope' => RoleModel::class] + $owned)))->toHaveKey('scope')
        ->and(roleErrors(fn () => createRole(['name' => 'X', 'scope' => 'Not\\A\\Model'])))->toHaveKey('scope')
        ->and(roleErrors(fn () => createRole(['name' => 'X', 'scope_type' => 'Not\\A\\Model', 'scope_id' => '1'])))->toHaveKey('scope_type')
        ->and(roleErrors(fn () => createRole(['name' => 'X', 'scope_type' => FolderScope::class, 'scope_id' => 'missing'])))->toHaveKey('scope_type')
        ->and(roleErrors(fn () => createRole(['name' => 'Admin'])))->toHaveKey('slug')
        ->and(roleErrors(fn () => createRole(['name' => 'X', 'permissions' => ['no.such.permission']])))->toHaveKey('permissions.0');
});

it('renames a role and replaces its permissions', function (): void {
    Event::fake([RoleUpdatingActionEvent::class, RoleUpdatedActionEvent::class]);
    $role = createRole(['name' => 'Ops', 'permissions' => ['guardian.sso.view']]);

    $updated = app(UpdateRoleAction::class)->execute($role, UpdateRoleData::from(['name' => 'Operations', 'permissions' => ['guardian.scim.manage']]));

    expect($updated->name)->toBe('Operations')
        ->and($updated->slug)->toBe('ops')
        ->and($updated->permissions->pluck('name')->all())->toBe(['guardian.scim.manage']);

    // Fields left out are kept.
    $kept = app(UpdateRoleAction::class)->execute($updated, UpdateRoleData::from(['description' => 'On call']));

    expect($kept->name)->toBe('Operations')->and($kept->permissions->pluck('name')->all())->toBe(['guardian.scim.manage']);

    Event::assertDispatchedTimes(RoleUpdatingActionEvent::class, 2);
    Event::assertDispatchedTimes(RoleUpdatedActionEvent::class, 2);
});

it('forgets remembered answers when a role\'s permissions change', function (): void {
    $ada = user();
    $role = createRole(['name' => 'Ops']);
    assignDirectly($ada, $role);

    expect(app(Permissions::class)->allows($ada, 'guardian.sso.view'))->toBeFalse();

    app(UpdateRoleAction::class)->execute($role, UpdateRoleData::from(['permissions' => ['guardian.sso.view']]));

    expect(app(Permissions::class)->allows($ada, 'guardian.sso.view'))->toBeTrue();
});

it('deletes a role with every assignment of it, but never a built-in one', function (): void {
    Event::fake([RoleDeletingActionEvent::class, RoleDeletedActionEvent::class]);
    $role = createRole(['name' => 'Temp', 'permissions' => ['guardian.sso.view']]);
    assignDirectly(user(), $role);

    app(DeleteRoleAction::class)->execute($role);

    expect(RoleModel::query()->whereKey($role->id)->exists())->toBeFalse()
        ->and(RoleAssignmentModel::query()->where('role_id', $role->id)->count())->toBe(0);

    Event::assertDispatched(RoleDeletingActionEvent::class);
    Event::assertDispatched(RoleDeletedActionEvent::class);

    $admin = RoleModel::query()->where('slug', 'admin')->sole();

    expect(roleErrors(fn () => app(DeleteRoleAction::class)->execute($admin)))->toHaveKey('role')
        ->and(RoleModel::query()->whereKey($admin->id)->exists())->toBeTrue();
});

it('shows a role with its permissions', function (): void {
    Event::fake([RoleShowingActionEvent::class, RoleShownActionEvent::class]);
    $admin = RoleModel::query()->where('slug', 'admin')->sole();

    $shown = app(ShowRoleAction::class)->execute($admin);

    expect($shown->relationLoaded('permissions'))->toBeTrue()
        ->and($shown->permissions->pluck('name'))->toContain('guardian.roles.view');

    Event::assertDispatched(RoleShowingActionEvent::class);
    Event::assertDispatched(RoleShownActionEvent::class);
});

it('lists the shared roles, plus a scope\'s own when it names one, filtered by scope', function (): void {
    Event::fake([RolesListingActionEvent::class, RolesListedActionEvent::class]);
    $folder = folder();
    $other = folder();
    createRole(['name' => 'Editors', 'scope_type' => FolderScope::class, 'scope_id' => $folder->id]);
    createRole(['name' => 'Others', 'scope_type' => FolderScope::class, 'scope_id' => $other->id]);

    $slugs = fn (array $filters): array => app(ListRolesAction::class)->execute(ListRolesData::from($filters))->getCollection()->pluck('slug')->sort()->values()->all();

    expect($slugs([]))->toBe(['admin', 'member', 'super-admin'])
        ->and($slugs(['scope_type' => FolderScope::class, 'scope_id' => $folder->id]))->toBe(['admin', 'editors', 'member', 'super-admin'])
        ->and($slugs(['scope' => 'global']))->toBe(['super-admin']);

    Event::assertDispatched(RolesListingActionEvent::class);
    Event::assertDispatched(RolesListedActionEvent::class);
});

describe('the escalation guard', function (): void {
    beforeEach(fn () => config(['guardian.authorization.enabled' => true]));

    it('lets an actor grant only what they hold in the scope, naming what they lack', function (): void {
        $folder = folder();
        $lead = user();
        assignDirectly($lead, roleGranting('lead', ['guardian.roles.manage', 'guardian.sso.view']), $folder);
        $owned = ['scope_type' => FolderScope::class, 'scope_id' => $folder->id];

        $errors = roleErrors(fn () => createRole(['name' => 'X', 'permissions' => ['guardian.sso.view', 'guardian.scim.manage', 'guardian.roles.assign']] + $owned, $lead));

        expect($errors['permissions'][0] ?? '')->toContain('guardian.roles.assign, guardian.scim.manage')
            ->not->toContain('guardian.sso.view');

        expect(createRole(['name' => 'Viewers', 'permissions' => ['guardian.sso.view']] + $owned, $lead)->slug)->toBe('viewers')
            // Held in the folder, not globally: a shared role is checked globally.
            ->and(roleErrors(fn () => createRole(['name' => 'Shared', 'permissions' => ['guardian.sso.view']], $lead)))->toHaveKey('permissions');
    });

    it('checks only the permissions an update adds', function (): void {
        $lead = user();
        assignDirectly($lead, roleGranting('lead', ['guardian.roles.manage', 'guardian.sso.view']));
        $role = roleGranting('ops', ['guardian.scim.manage']);

        expect(roleErrors(fn () => app(UpdateRoleAction::class)->execute($role, UpdateRoleData::from(['permissions' => ['guardian.scim.manage', 'guardian.sso.manage']]), $lead)))
            ->toHaveKey('permissions')
            ->and(app(UpdateRoleAction::class)->execute($role, UpdateRoleData::from(['permissions' => ['guardian.scim.manage', 'guardian.sso.view']]), $lead)->permissions)->toHaveCount(2);
    });

    it('leaves super roles to super-admins', function (): void {
        $lead = user();
        assignDirectly($lead, roleGranting('lead', ['guardian.roles.manage']));
        $boss = user();
        grant($boss, 'super-admin');
        $super = RoleModel::query()->where('slug', 'super-admin')->sole();
        $custom = roleGranting('root', [], super: true);

        expect(roleErrors(fn () => createRole(['name' => 'Gods', 'super' => true], $lead)))->toHaveKey('super')
            ->and(roleErrors(fn () => app(UpdateRoleAction::class)->execute($super, UpdateRoleData::from(['name' => 'Boss']), $lead)))->toHaveKey('role')
            ->and(roleErrors(fn () => app(DeleteRoleAction::class)->execute($custom, $lead)))->toHaveKey('role')
            ->and(createRole(['name' => 'Gods', 'super' => true], $boss)->super)->toBeTrue()
            ->and(app(UpdateRoleAction::class)->execute($super, UpdateRoleData::from(['name' => 'Boss']), $boss)->name)->toBe('Boss');

        app(DeleteRoleAction::class)->execute($custom, $boss);

        expect(RoleModel::query()->whereKey($custom->id)->exists())->toBeFalse();
    });

    it('trusts code calls without an actor, and everything while authorization is off', function (): void {
        expect(createRole(['name' => 'Gods', 'super' => true, 'permissions' => ['guardian.roles.manage']])->super)->toBeTrue();

        config(['guardian.authorization.enabled' => false]);

        expect(createRole(['name' => 'More', 'super' => true], user())->super)->toBeTrue();
    });
});
