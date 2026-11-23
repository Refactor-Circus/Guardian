<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;

// Criterion 17: the roles API by route name. Behaviour is covered by
// RoleActionsTest; these check the wiring, the answers and the checks.

it('manages roles over HTTP, naming them by slug', function (): void {
    $this->postJson(route('guardian.roles.store'), ['name' => 'Ops', 'permissions' => ['guardian.sso.view']])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'ops')
        ->assertJsonPath('data.scope', 'any')
        ->assertJsonPath('data.permissions', ['guardian.sso.view'])
        ->assertJsonMissingPath('data.id');

    $this->getJson(route('guardian.roles.show', 'ops'))
        ->assertOk()
        ->assertJsonPath('data.name', 'Ops')
        ->assertJsonPath('data.created_at', fn (string $at): bool => preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d[+-]\d\d:\d\d$/', $at) === 1);

    $this->patchJson(route('guardian.roles.update', 'ops'), ['name' => 'Operations'])->assertOk()->assertJsonPath('data.name', 'Operations');
    $this->getJson(route('guardian.roles.index'))->assertOk()->assertJsonPath('meta.total', 4);
    $this->deleteJson(route('guardian.roles.destroy', 'ops'))->assertNoContent();
    $this->getJson(route('guardian.roles.show', 'ops'))->assertNotFound();
    $this->deleteJson(route('guardian.roles.destroy', 'admin'))->assertUnprocessable()->assertJsonValidationErrors('role');
});

it('reaches a scope\'s own roles through scope_type and scope_id', function (): void {
    $folder = folder();
    $scope = ['scope_type' => FolderScope::class, 'scope_id' => $folder->id];

    $this->postJson(route('guardian.roles.store'), ['name' => 'Editors'] + $scope)->assertCreated()->assertJsonPath('data.owner_id', $folder->id);

    $this->getJson(route('guardian.roles.show', ['editors']))->assertNotFound();
    $this->getJson(route('guardian.roles.show', ['editors'] + $scope))->assertOk();
    $this->getJson(route('guardian.roles.index', $scope))->assertOk()->assertJsonPath('meta.total', 4);
    $this->patchJson(route('guardian.roles.update', ['editors']), ['description' => 'Edit'] + $scope)->assertOk()->assertJsonPath('data.description', 'Edit');
    $this->deleteJson(route('guardian.roles.destroy', ['editors'] + $scope))->assertNoContent();
    $this->getJson(route('guardian.roles.index', ['scope_type' => FolderScope::class]))->assertUnprocessable()->assertJsonValidationErrors('scope_id');
});

describe('authorization', function (): void {
    beforeEach(fn () => config(['guardian.authorization.enabled' => true]));

    it('asks guests to sign in and refuses users without the permission', function (): void {
        $this->getJson(route('guardian.roles.index'))->assertUnauthorized();
        $this->postJson(route('guardian.roles.store'), ['name' => 'X'])->assertUnauthorized();

        $this->actingAs(user());

        $this->getJson(route('guardian.roles.index'))->assertForbidden();
        $this->getJson(route('guardian.roles.show', 'admin'))->assertForbidden();
        $this->postJson(route('guardian.roles.store'), ['name' => 'X'])->assertForbidden();
        $this->patchJson(route('guardian.roles.update', 'member'), ['name' => 'X'])->assertForbidden();
        $this->deleteJson(route('guardian.roles.destroy', 'member'))->assertForbidden();
    });

    it('checks guardian.roles.view and guardian.roles.manage in the scope the request names', function (): void {
        $folder = folder();
        $scope = ['scope_type' => FolderScope::class, 'scope_id' => $folder->id];
        $ada = user();
        grant($ada, 'admin', $folder);
        $this->actingAs($ada);

        // admin views roles (guardian.roles.view) in the folder, not elsewhere.
        $this->getJson(route('guardian.roles.index', $scope))->assertOk();
        $this->getJson(route('guardian.roles.show', ['member'] + $scope))->assertOk();
        $this->getJson(route('guardian.roles.index'))->assertForbidden();
        $this->getJson(route('guardian.roles.index', ['scope_type' => FolderScope::class, 'scope_id' => folder()->id]))->assertForbidden();
        // ... and manages none.
        $this->postJson(route('guardian.roles.store'), ['name' => 'X'] + $scope)->assertForbidden();

        $lead = user();
        assignDirectly($lead, roleGranting('lead', ['guardian.roles.manage', 'guardian.sso.view']), $folder);
        $this->actingAs($lead);

        $this->postJson(route('guardian.roles.store'), ['name' => 'Viewers', 'permissions' => ['guardian.sso.view']] + $scope)->assertCreated();
        $this->postJson(route('guardian.roles.store'), ['name' => 'Wide', 'permissions' => ['guardian.scim.manage']] + $scope)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions' => 'guardian.scim.manage']);
        $this->patchJson(route('guardian.roles.update', ['viewers']), ['name' => 'Readers'] + $scope)->assertOk();
        // A shared role is managed globally.
        $this->postJson(route('guardian.roles.store'), ['name' => 'Shared'])->assertForbidden();
        $this->patchJson(route('guardian.roles.update', ['member']), ['name' => 'X'] + $scope)->assertForbidden();

        expect(RoleModel::query()->where('slug', 'viewers')->exists())->toBeTrue();
    });
});
