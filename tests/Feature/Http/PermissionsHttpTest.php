<?php

declare(strict_types=1);

use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;

// Criterion 17: the permissions API by route name.

it('manages permissions over HTTP, naming them by name', function (): void {
    $this->postJson(route('guardian.permissions.store'), ['name' => 'invoices.edit', 'description' => 'Edit invoices'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'invoices.edit')
        ->assertJsonPath('data.system', false)
        ->assertJsonMissingPath('data.id');

    $this->postJson(route('guardian.permissions.store'), ['name' => 'invoices.edit'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->getJson(route('guardian.permissions.index', ['search' => 'invoices']))->assertOk()->assertJsonPath('meta.total', 1);
    $this->patchJson(route('guardian.permissions.update', 'invoices.edit'), ['name' => 'invoices.update'])->assertOk()->assertJsonPath('data.name', 'invoices.update');
    $this->patchJson(route('guardian.permissions.update', 'guardian.roles.view'), ['name' => 'x.y'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->deleteJson(route('guardian.permissions.destroy', 'guardian.roles.view'))->assertUnprocessable();
    $this->deleteJson(route('guardian.permissions.destroy', 'invoices.update'))->assertNoContent();
    $this->deleteJson(route('guardian.permissions.destroy', 'invoices.update'))->assertNotFound();

    expect(PermissionModel::query()->where('name', 'like', 'invoices%')->count())->toBe(0);
});

it('needs guardian.roles.view to list and guardian.roles.manage to change permissions', function (): void {
    config(['guardian.authorization.enabled' => true]);

    $this->getJson(route('guardian.permissions.index'))->assertUnauthorized();
    $this->postJson(route('guardian.permissions.store'), ['name' => 'a.b'])->assertUnauthorized();

    $admin = user();
    grant($admin, 'admin');
    $this->actingAs($admin);

    $this->getJson(route('guardian.permissions.index'))->assertOk();
    $this->postJson(route('guardian.permissions.store'), ['name' => 'a.b'])->assertForbidden();
    $this->patchJson(route('guardian.permissions.update', 'guardian.sso.view'), ['description' => 'x'])->assertForbidden();
    $this->deleteJson(route('guardian.permissions.destroy', 'guardian.sso.view'))->assertForbidden();

    $this->actingAs(user())->getJson(route('guardian.permissions.index'))->assertForbidden();
});
