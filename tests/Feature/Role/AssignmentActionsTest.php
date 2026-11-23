<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Guardian\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Guardian\Domains\Role\Actions\ListRoleAssignmentsAction;
use RefactorCircus\Guardian\Domains\Role\Actions\ListUserAbilitiesAction;
use RefactorCircus\Guardian\Domains\Role\Actions\RevokeRoleAction;
use RefactorCircus\Guardian\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Guardian\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Guardian\Domains\Role\Data\ListUserAbilitiesData;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssignedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssigningActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssignmentsListedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleAssignmentsListingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleRevokedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\RoleRevokingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\UserAbilitiesListedActionEvent;
use RefactorCircus\Guardian\Domains\Role\Events\UserAbilitiesListingActionEvent;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use Workbench\App\Models\User;

// Criteria 15 and 16: assigning, revoking and listing roles, and a user's
// abilities in a scope.

/**
 * @param  array<string, mixed>  $input
 */
function assignRole(User $user, string $role, ?FolderScope $scope = null, mixed $actor = null, array $input = []): RoleAssignmentModel
{
    return app(AssignRoleAction::class)->execute(AssignRoleData::from([
        'user' => (string) $user->getKey(),
        'role' => $role,
        'scope_type' => $scope === null ? null : FolderScope::class,
        'scope_id' => $scope?->id,
    ] + $input), $actor);
}

/**
 * @return array<string, array<int, string>>
 */
function assignmentErrors(Closure $call): array
{
    try {
        $call();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

it('assigns a role globally and in a scope, and answers from it at once', function (): void {
    Event::fake([RoleAssigningActionEvent::class, RoleAssignedActionEvent::class]);
    $ada = user();
    $folder = folder();

    expect(app(Permissions::class)->allows($ada, 'guardian.roles.view', $folder))->toBeFalse();

    $scoped = assignRole($ada, 'admin', $folder);
    $global = assignRole($ada, 'member');

    expect($scoped->scope_type)->toBe($folder->getMorphClass())
        ->and($scoped->scope_id)->toBe($folder->id)
        ->and($global->scope_type)->toBeNull()
        ->and(app(Permissions::class)->allows($ada, 'guardian.roles.view', $folder))->toBeTrue()
        ->and(app(Permissions::class)->allows($ada, 'guardian.roles.view'))->toBeFalse();

    Event::assertDispatchedTimes(RoleAssigningActionEvent::class, 2);
    Event::assertDispatched(RoleAssignedActionEvent::class, fn (RoleAssignedActionEvent $event): bool => $event->assignment->is($scoped));
});

it('assigns a scope\'s own role only there, and refuses roles that do not fit', function (): void {
    $ada = user();
    $folder = folder();
    roleGranting('editors', [], owner: $folder);
    roleGranting('typed', [], scope: (new FolderScope)->getMorphClass());

    expect(assignRole($ada, 'editors', $folder)->role?->owner_id)->toBe($folder->id)
        ->and(assignRole($ada, 'typed', $folder)->role?->slug)->toBe('typed')
        ->and(assignmentErrors(fn () => assignRole($ada, 'editors', folder())))->toHaveKey('role')
        ->and(assignmentErrors(fn () => assignRole($ada, 'typed')))->toHaveKey('role')
        ->and(assignmentErrors(fn () => assignRole($ada, 'super-admin', $folder)))->toHaveKey('role')
        ->and(assignmentErrors(fn () => assignRole($ada, 'nope')))->toHaveKey('role')
        ->and(assignmentErrors(fn () => assignRole($ada, 'editors', $folder)))->toHaveKey('role')
        ->and(assignmentErrors(fn () => app(AssignRoleAction::class)->execute(AssignRoleData::from(['user' => '999999', 'role' => 'member']))))->toHaveKey('user');
});

it('revokes an assignment, forgetting its holder\'s grants', function (): void {
    Event::fake([RoleRevokingActionEvent::class, RoleRevokedActionEvent::class]);
    $ada = user();
    $folder = folder();
    $assignment = assignRole($ada, 'admin', $folder);

    expect(app(Permissions::class)->allows($ada, 'guardian.roles.view', $folder))->toBeTrue();

    app(RevokeRoleAction::class)->execute($assignment);

    expect(RoleAssignmentModel::query()->count())->toBe(0)
        ->and(app(Permissions::class)->allows($ada, 'guardian.roles.view', $folder))->toBeFalse();

    Event::assertDispatched(RoleRevokingActionEvent::class);
    Event::assertDispatched(RoleRevokedActionEvent::class);
});

it('lists assignments by user, role, scope type and scope id', function (): void {
    Event::fake([RoleAssignmentsListingActionEvent::class, RoleAssignmentsListedActionEvent::class]);
    $ada = user();
    $bob = user();
    $folder = folder();
    $other = folder();
    assignRole($ada, 'admin', $folder);
    assignRole($ada, 'member');
    assignRole($bob, 'member', $other);

    $count = fn (array $filters): int => app(ListRoleAssignmentsAction::class)->execute(ListRoleAssignmentsData::from($filters))->total();

    expect($count([]))->toBe(3)
        ->and($count(['user' => (string) $ada->getKey()]))->toBe(2)
        ->and($count(['role' => 'member']))->toBe(2)
        ->and($count(['scope_type' => FolderScope::class]))->toBe(2)
        ->and($count(['scope_type' => FolderScope::class, 'scope_id' => $other->id]))->toBe(1)
        ->and($count(['scope_id' => $folder->id]))->toBe(1)
        ->and($count(['user' => (string) $bob->getKey(), 'role' => 'admin']))->toBe(0)
        ->and(assignmentErrors(fn () => $count(['scope_type' => 'Not\\A\\Model'])))->toHaveKey('scope_type');

    Event::assertDispatched(RoleAssignmentsListingActionEvent::class);
    Event::assertDispatched(RoleAssignmentsListedActionEvent::class);
});

it('lists a user\'s abilities in a scope, inherited from its parents', function (): void {
    Event::fake([UserAbilitiesListingActionEvent::class, UserAbilitiesListedActionEvent::class]);
    $ada = user();
    $parent = folder();
    $child = folder(['parent_id' => $parent->id]);
    assignDirectly($ada, roleGranting('viewer', ['guardian.sso.view']), $parent);
    assignDirectly($ada, roleGranting('scimmer', ['guardian.scim.manage']));

    $abilities = fn (?FolderScope $scope) => app(ListUserAbilitiesAction::class)->execute($ada, ListUserAbilitiesData::from($scope === null ? [] : ['scope_type' => FolderScope::class, 'scope_id' => $scope->id]));

    expect($abilities(null)->abilities)->toBe(['guardian.scim.manage'])
        ->and($abilities($child)->abilities)->toBe(['guardian.scim.manage', 'guardian.sso.view'])
        ->and($abilities($child)->scopeId)->toBe($child->id)
        ->and($abilities($child)->superAdmin)->toBeFalse();

    $boss = user();
    grant($boss, 'super-admin');
    $super = app(ListUserAbilitiesAction::class)->execute($boss);

    expect($super->superAdmin)->toBeTrue()->and($super->abilities)->toContain('guardian.roles.manage');

    Event::assertDispatched(UserAbilitiesListingActionEvent::class);
    Event::assertDispatched(UserAbilitiesListedActionEvent::class);
});

describe('the escalation guard', function (): void {
    beforeEach(fn () => config(['guardian.authorization.enabled' => true]));

    it('needs the actor to hold every permission of the role in the scope, naming what is missing', function (): void {
        $folder = folder();
        $lead = user();
        assignDirectly($lead, roleGranting('lead', ['guardian.roles.assign', 'guardian.roles.view']), $folder);
        roleGranting('helper', ['guardian.roles.view']);

        $errors = assignmentErrors(fn () => assignRole(user(), 'admin', $folder, $lead));

        expect($errors['role'][0] ?? '')->toContain('guardian.scim.manage')->toContain('guardian.sso.manage')->not->toContain('guardian.roles.view')
            ->and(assignRole(user(), 'helper', $folder, $lead)->role?->slug)->toBe('helper')
            // Held in the folder only: not elsewhere, not globally.
            ->and(assignmentErrors(fn () => assignRole(user(), 'helper', folder(), $lead)))->toHaveKey('role')
            ->and(assignmentErrors(fn () => assignRole(user(), 'helper', null, $lead)))->toHaveKey('role');
    });

    it('guards revoking the same way', function (): void {
        $folder = folder();
        $lead = user();
        assignDirectly($lead, roleGranting('lead', ['guardian.roles.assign', 'guardian.roles.view']), $folder);
        $admin = assignRole(user(), 'admin', $folder);
        $helper = assignRole(user(), 'lead', $folder);

        expect(assignmentErrors(fn () => app(RevokeRoleAction::class)->execute($admin, $lead)))->toHaveKey('role');

        app(RevokeRoleAction::class)->execute($helper, $lead);

        expect(RoleAssignmentModel::query()->whereKey($helper->id)->exists())->toBeFalse();
    });

    it('lets only super-admins give or take a super role', function (): void {
        $lead = user();
        assignDirectly($lead, roleGranting('lead', ['guardian.roles.assign']));
        $boss = user();
        grant($boss, 'super-admin');
        $target = user();

        expect(assignmentErrors(fn () => assignRole($target, 'super-admin', null, $lead)))->toHaveKey('role');

        $assignment = assignRole($target, 'super-admin', null, $boss);

        expect(assignmentErrors(fn () => app(RevokeRoleAction::class)->execute($assignment, $lead)))->toHaveKey('role');

        app(RevokeRoleAction::class)->execute($assignment, $boss);

        expect(RoleAssignmentModel::query()->whereKey($assignment->id)->exists())->toBeFalse();
    });
});
