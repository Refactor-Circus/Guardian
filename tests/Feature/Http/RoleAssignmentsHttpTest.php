<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;

// Criterion 17: assignments and a user's abilities, by route name.

it('assigns, lists and revokes roles over HTTP', function (): void {
    $ada = user();
    $folder = folder();
    $scope = ['scope_type' => FolderScope::class, 'scope_id' => $folder->id];

    $id = $this->postJson(route('guardian.role-assignments.store'), ['user' => $ada->getKey(), 'role' => 'admin'] + $scope)
        ->assertCreated()
        ->assertJsonPath('data.user_id', (string) $ada->getKey())
        ->assertJsonPath('data.role.slug', 'admin')
        ->assertJsonPath('data.scope_id', $folder->id)
        ->json('data.id');

    $this->postJson(route('guardian.role-assignments.store'), ['user' => $ada->getKey(), 'role' => 'admin'] + $scope)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');

    $this->getJson(route('guardian.role-assignments.index', ['user' => $ada->getKey()]))->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $id);
    $this->getJson(route('guardian.role-assignments.index', ['role' => 'member']))->assertOk()->assertJsonPath('meta.total', 0);

    $this->getJson(route('guardian.users.abilities.index', [$ada->getKey()] + $scope))
        ->assertOk()
        ->assertJsonPath('data.super_admin', false)
        ->assertJsonPath('data.scope_id', $folder->id)
        ->assertJsonPath('data.abilities', fn (array $abilities): bool => in_array('guardian.roles.view', $abilities, true));
    $this->getJson(route('guardian.users.abilities.index', $ada->getKey()))->assertOk()->assertJsonPath('data.abilities', []);
    $this->getJson(route('guardian.users.abilities.index', 999999))->assertNotFound();

    $this->deleteJson(route('guardian.role-assignments.destroy', $id))->assertNoContent();
    $this->deleteJson(route('guardian.role-assignments.destroy', $id))->assertNotFound();

    expect(RoleAssignmentModel::query()->count())->toBe(0);
});

describe('authorization', function (): void {
    beforeEach(fn () => config(['guardian.authorization.enabled' => true]));

    it('asks guests to sign in and refuses users without the permission', function (): void {
        $ada = user();
        $assignment = assignDirectly($ada, roleGranting('helper', []));

        $this->getJson(route('guardian.role-assignments.index'))->assertUnauthorized();
        $this->postJson(route('guardian.role-assignments.store'), ['user' => $ada->getKey(), 'role' => 'member'])->assertUnauthorized();
        $this->getJson(route('guardian.users.abilities.index', $ada->getKey()))->assertUnauthorized();

        $this->actingAs(user());

        $this->getJson(route('guardian.role-assignments.index'))->assertForbidden();
        $this->postJson(route('guardian.role-assignments.store'), ['user' => $ada->getKey(), 'role' => 'member'])->assertForbidden();
        $this->deleteJson(route('guardian.role-assignments.destroy', $assignment->id))->assertForbidden();
        $this->getJson(route('guardian.users.abilities.index', $ada->getKey()))->assertForbidden();
    });

    it('checks guardian.roles.assign and guardian.roles.view in the scope the request names', function (): void {
        $folder = folder();
        $scope = ['scope_type' => FolderScope::class, 'scope_id' => $folder->id];
        $lead = user();
        assignDirectly($lead, roleGranting('lead', ['guardian.roles.assign', 'guardian.roles.view']), $folder);
        $target = user();
        $this->actingAs($lead);

        $id = $this->postJson(route('guardian.role-assignments.store'), ['user' => $target->getKey(), 'role' => 'lead'] + $scope)->assertCreated()->json('data.id');
        $this->postJson(route('guardian.role-assignments.store'), ['user' => $target->getKey(), 'role' => 'lead'])->assertForbidden();
        $this->postJson(route('guardian.role-assignments.store'), ['user' => $target->getKey(), 'role' => 'admin'] + $scope)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role' => 'guardian.sso.manage']);

        $this->getJson(route('guardian.role-assignments.index', $scope))->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson(route('guardian.role-assignments.index'))->assertForbidden();
        $this->getJson(route('guardian.users.abilities.index', [$target->getKey()] + $scope))->assertOk();
        $this->getJson(route('guardian.users.abilities.index', $target->getKey()))->assertForbidden();

        $this->deleteJson(route('guardian.role-assignments.destroy', $id))->assertNoContent();

        // Everyone may list their own abilities.
        $this->actingAs($target)->getJson(route('guardian.users.abilities.index', $target->getKey()))->assertOk();
    });
});
