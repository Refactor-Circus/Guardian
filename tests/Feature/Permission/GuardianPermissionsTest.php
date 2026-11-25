<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Support\Tables;

/*
 * Criteria 10, 11 and 13: Guardian's answers to Keystone's Permissions, on
 * Roster-free fixture scopes (tests/Fixtures/Scopes).
 */

function guardianPermissions(): GuardianPermissions
{
    return app(GuardianPermissions::class);
}

describe('allows()', function (): void {
    it('refuses a guest', function (): void {
        expect(guardianPermissions()->allows(null, 'guardian.roles.view'))->toBeFalse();
    });

    it('allows a holder of a super role anything, everywhere', function (): void {
        $ada = user();
        grant($ada, 'super-admin');

        expect(guardianPermissions()->allows($ada, 'guardian.roles.manage'))->toBeTrue()
            ->and(guardianPermissions()->allows($ada, 'guardian.roles.manage', folder()))->toBeTrue()
            ->and(guardianPermissions()->isSuperAdmin($ada))->toBeTrue();
    });

    it('allows a configured super-admin only with a verified email', function (): void {
        config(['guardian.super_admins' => ['Boss@Example.com']]);
        $verified = user(['email' => 'boss@example.com', 'email_verified_at' => now()]);

        expect(guardianPermissions()->isSuperAdmin($verified))->toBeTrue()
            ->and(guardianPermissions()->allows($verified, 'guardian.roles.manage'))->toBeTrue();

        $verified->forceFill(['email_verified_at' => null])->save();
        guardianPermissions()->forget();

        expect(guardianPermissions()->isSuperAdmin($verified))->toBeFalse()
            ->and(guardianPermissions()->allows($verified, 'guardian.roles.manage'))->toBeFalse();
    });

    it('allows what a global assignment grants, in every scope', function (): void {
        $ada = user();
        assignDirectly($ada, roleGranting('viewer', ['guardian.roles.view']));

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeTrue()
            ->and(guardianPermissions()->allows($ada, 'guardian.roles.view', folder()))->toBeTrue()
            ->and(guardianPermissions()->allows($ada, 'guardian.roles.manage'))->toBeFalse();
    });

    it('allows what an assignment grants in its scope and the scopes below it, not above or beside', function (): void {
        $ada = user();
        $parent = folder(['name' => 'Parent']);
        $child = folder(['name' => 'Child', 'parent_id' => $parent->id]);
        $sibling = folder(['name' => 'Sibling']);
        assignDirectly($ada, roleGranting('viewer', ['guardian.roles.view']), $parent);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view', $parent))->toBeTrue()
            ->and(guardianPermissions()->allows($ada, 'guardian.roles.view', $child))->toBeTrue()
            ->and(guardianPermissions()->allows($ada, 'guardian.roles.view', $sibling))->toBeFalse()
            ->and(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeFalse();
    });

    it('lets a scope grant by itself, up its chain', function (): void {
        $owner = user();
        $parent = folder(['owner_id' => $owner->getKey()]);
        $child = folder(['parent_id' => $parent->id]);

        expect(guardianPermissions()->allows($owner, 'guardian.roles.view', $child))->toBeTrue()
            ->and(guardianPermissions()->allows($owner, 'guardian.roles.manage', $child))->toBeFalse()
            ->and(guardianPermissions()->allows(user(), 'guardian.roles.view', $child))->toBeFalse();
    });

    it('counts only itself for a scope that is a model but not a Scope', function (): void {
        $ada = user();
        $other = user();
        assignDirectly($ada, roleGranting('viewer', ['guardian.roles.view']), $other);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view', $other))->toBeTrue()
            ->and(guardianPermissions()->allows($ada, 'guardian.roles.view', user()))->toBeFalse();
    });

    it('refuses an ability it has no permission row for', function (): void {
        $ada = user();
        assignDirectly($ada, roleGranting('viewer', ['guardian.roles.view']));
        DB::table(Tables::name('permission_role'))->insert([
            'role_id' => RoleModel::query()->where('slug', 'viewer')->value('id'),
            'permission_id' => PermissionModel::query()->create(['name' => 'acme.widgets.view'])->id,
        ]);
        PermissionModel::query()->where('name', 'acme.widgets.view')->delete();

        expect(guardianPermissions()->allows($ada, 'acme.widgets.view'))->toBeFalse()
            ->and(guardianPermissions()->allows($ada, 'nothing.at.all'))->toBeFalse();
    });
});

describe('scopesWith(), abilities()', function (): void {
    it('is null for a global holder or a super-admin, otherwise the scope keys of that type', function (): void {
        $global = user();
        assignDirectly($global, roleGranting('viewer', ['guardian.roles.view']));

        $scoped = user();
        $first = folder();
        $second = folder();
        $role = roleGranting('folder-viewer', ['guardian.roles.view']);
        assignDirectly($scoped, $role, $first);
        assignDirectly($scoped, $role, $second);
        assignDirectly($scoped, roleGranting('other', ['guardian.roles.assign']), folder());
        assignDirectly($scoped, $role, user());

        $boss = user();
        grant($boss, 'super-admin');

        $type = $first->getMorphClass();
        $keys = guardianPermissions()->scopesWith($scoped, 'guardian.roles.view', $type);
        sort($keys);
        $expected = [(string) $first->id, (string) $second->id];
        sort($expected);

        expect(guardianPermissions()->scopesWith($global, 'guardian.roles.view', $type))->toBeNull()
            ->and(guardianPermissions()->scopesWith($boss, 'guardian.roles.view', $type))->toBeNull()
            ->and($keys)->toBe($expected)
            ->and(guardianPermissions()->scopesWith(user(), 'guardian.roles.view', $type))->toBe([])
            ->and(guardianPermissions()->scopesWith(null, 'guardian.roles.view', $type))->toBe([]);
    });

    it('lists the abilities held in a scope, sorted, and every one for a super-admin', function (): void {
        $ada = user();
        $parent = folder();
        $child = folder(['parent_id' => $parent->id]);
        assignDirectly($ada, roleGranting('viewer', ['guardian.roles.view']));
        assignDirectly($ada, roleGranting('assigner', ['guardian.roles.assign', 'acme.b', 'acme.a']), $parent);

        $boss = user();
        grant($boss, 'super-admin');

        expect(guardianPermissions()->abilities($ada))->toBe(['guardian.roles.view'])
            ->and(guardianPermissions()->abilities($ada, $child))->toBe(['acme.a', 'acme.b', 'guardian.roles.assign', 'guardian.roles.view'])
            ->and(guardianPermissions()->abilities($boss))->toBe(PermissionModel::query()->orderBy('name')->pluck('name')->all())
            ->and(guardianPermissions()->abilities(null))->toBe([]);
    });
});

describe('remembering', function (): void {
    it('remembers a user\'s grants for the request until forgotten', function (): void {
        $ada = user();
        $role = roleGranting('viewer', ['guardian.roles.view']);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeFalse();

        // Written without Guardian's models: nothing tells it.
        DB::table(Tables::name('role_assignments'))->insert(['id' => (string) Str::ulid(), 'role_id' => $role->id, 'user_id' => $ada->getKey()]);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeFalse();

        guardianPermissions()->forget($ada);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeTrue();
    });

    it('forgets everyone at once', function (): void {
        $ada = user();
        $role = roleGranting('viewer', ['guardian.roles.view']);
        guardianPermissions()->allows($ada, 'guardian.roles.view');

        DB::table(Tables::name('role_assignments'))->insert(['id' => (string) Str::ulid(), 'role_id' => $role->id, 'user_id' => $ada->getKey()]);
        guardianPermissions()->forget();

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeTrue();
    });

    it('forgets what Guardian\'s models change, as they change it', function (): void {
        $ada = user();
        $role = roleGranting('viewer', ['guardian.roles.view']);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeFalse();

        $assignment = assignDirectly($ada, $role);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeTrue();

        $assignment->delete();

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeFalse();

        assignDirectly($ada, $role);
        $role->update(['super' => true]);

        expect(guardianPermissions()->isSuperAdmin($ada))->toBeTrue();

        PermissionModel::query()->create(['name' => 'acme.new']);

        expect(guardianPermissions()->abilities($ada))->toContain('acme.new');
    });

    it('keeps names and grants in the cache between requests while a TTL is set, and forgets them on writes', function (): void {
        config(['guardian.permission_cache.ttl' => 300, 'guardian.permission_cache.store' => 'array']);
        $ada = user();
        $role = roleGranting('viewer', ['guardian.roles.view']);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeFalse();

        // A new request: a fresh instance, the same cache.
        app()->forgetScopedInstances();
        DB::table(Tables::name('role_assignments'))->insert(['id' => (string) Str::ulid(), 'role_id' => $role->id, 'user_id' => $ada->getKey()]);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeFalse();

        app()->forgetScopedInstances();
        assignDirectly(user(), $role);
        app()->forgetScopedInstances();

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeFalse();

        guardianPermissions()->forget($ada);
        app()->forgetScopedInstances();

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeTrue();

        $boss = user();
        grant($boss, 'super-admin');
        guardianPermissions()->abilities($boss);
        app()->forgetScopedInstances();

        PermissionModel::query()->create(['name' => 'acme.cached']);
        app()->forgetScopedInstances();

        expect(guardianPermissions()->abilities($boss))->toContain('acme.cached');
    });

    it('caches nothing with no TTL', function (): void {
        $ada = user();
        $role = roleGranting('viewer', ['guardian.roles.view']);
        guardianPermissions()->allows($ada, 'guardian.roles.view');

        app()->forgetScopedInstances();
        DB::table(Tables::name('role_assignments'))->insert(['id' => (string) Str::ulid(), 'role_id' => $role->id, 'user_id' => $ada->getKey()]);

        expect(guardianPermissions()->allows($ada, 'guardian.roles.view'))->toBeTrue();
    });
});
