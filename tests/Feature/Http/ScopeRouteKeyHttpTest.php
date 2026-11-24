<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Permission/helpers.php';

use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Tests\Fixtures\Scopes\FolderScope;

/*
 * A scope named the way Atrium's `guardian.access` tab names it on another
 * package's screen (D-051): `scope_type` is the morph type Guardian stores,
 * `scope_id` the record the screen passes, its route key (an organization's
 * slug). The folder stands in as a host's organization does: a ULID key and
 * a slug route key, so a lookup by key alone fails as it did on a real host
 * ("No scope of this type has this id").
 */

beforeEach(function (): void {
    $this->folder = folder(['name' => 'Acme', 'slug' => 'w78504']);
    $this->scope = ['scope_type' => FolderScope::class, 'scope_id' => 'w78504'];
});

it('lists, assigns and shows abilities in a scope named by its route key, storing its key', function (): void {
    $ada = user();
    $other = folder(['name' => 'Other']);
    assignDirectly(user(), roleGranting('helper', []), $other);

    $this->getJson(route('guardian.role-assignments.index', $this->scope))->assertOk()->assertJsonPath('meta.total', 0);

    $this->postJson(route('guardian.role-assignments.store'), ['user' => $ada->getKey(), 'role' => 'admin'] + $this->scope)
        ->assertCreated()
        ->assertJsonPath('data.scope_type', FolderScope::class)
        ->assertJsonPath('data.scope_id', $this->folder->id);

    $this->getJson(route('guardian.role-assignments.index', $this->scope))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.user_id', (string) $ada->getKey());

    $this->getJson(route('guardian.users.abilities.index', [$ada->getKey()] + $this->scope))
        ->assertOk()
        ->assertJsonPath('data.scope_id', $this->folder->id)
        ->assertJsonPath('data.abilities', fn (array $abilities): bool => in_array('guardian.roles.view', $abilities, true));

    $this->getJson(route('guardian.roles.index', $this->scope))->assertOk();

    expect(RoleAssignmentModel::query()->where('scope_id', $this->folder->id)->count())->toBe(1);
});

it('still takes the scope\'s key', function (): void {
    assignDirectly(user(), roleGranting('helper', []), $this->folder);

    $this->getJson(route('guardian.role-assignments.index', ['scope_type' => FolderScope::class, 'scope_id' => $this->folder->id]))
        ->assertOk()
        ->assertJsonPath('meta.total', 1);
});

it('refuses a scope no route key or key names', function (): void {
    $this->postJson(route('guardian.role-assignments.store'), ['user' => user()->getKey(), 'role' => 'admin', 'scope_type' => FolderScope::class, 'scope_id' => 'nobody'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scope_type');

    $this->getJson(route('guardian.role-assignments.index', ['scope_type' => FolderScope::class, 'scope_id' => 'nobody']))
        ->assertUnprocessable()
        ->assertJsonPath('message', 'No scope of this type has this id.');
});
