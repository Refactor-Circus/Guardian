<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Support\Users;
use Workbench\App\Models\User;

/*
 * Criteria 6 and 7: Guardian's role tables, their configured names and user
 * key type, and the built-in roles seeded when they are created.
 */

/**
 * Run the role tables migration again under other names.
 */
function migrateRoleTablesAs(string $prefix, string $keyType = 'int'): void
{
    config([
        'guardian.tables.permissions' => $prefix.'_permissions',
        'guardian.tables.roles' => $prefix.'_roles',
        'guardian.tables.permission_role' => $prefix.'_permission_role',
        'guardian.tables.role_assignments' => $prefix.'_role_assignments',
        'guardian.users.key_type' => $keyType,
    ]);

    (require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000001_create_guardian_role_tables.php')->up();
}

/**
 * @return list<string>
 */
function indexColumns(string $table, string $type): array
{
    return array_values(array_map(
        fn (array $index): string => implode(',', $index['columns']),
        array_filter(Schema::getIndexes($table), fn (array $index): bool => match ($type) {
            'unique' => $index['unique'] && ! $index['primary'],
            'primary' => $index['primary'],
            default => ! $index['unique'],
        }),
    ));
}

it('creates the four tables with their columns', function (): void {
    expect(Schema::hasColumns('guardian_permissions', ['id', 'name', 'description', 'system', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('guardian_roles', ['id', 'name', 'slug', 'scope', 'owner_type', 'owner_id', 'description', 'super', 'system', 'created_at', 'updated_at']))->toBeTrue()
        ->and(Schema::hasColumns('guardian_permission_role', ['role_id', 'permission_id']))->toBeTrue()
        ->and(Schema::hasColumns('guardian_role_assignments', ['id', 'role_id', 'user_id', 'scope_type', 'scope_id', 'created_at', 'updated_at']))->toBeTrue();
});

it('indexes them as the spec says', function (): void {
    expect(indexColumns('guardian_permissions', 'unique'))->toContain('name')
        ->and(indexColumns('guardian_roles', 'index'))->toContain('scope,owner_type,owner_id,slug')
        ->and(indexColumns('guardian_permission_role', 'primary'))->toBe(['role_id,permission_id'])
        ->and(indexColumns('guardian_role_assignments', 'unique'))->toContain('role_id,user_id,scope_type,scope_id')
        ->and(indexColumns('guardian_role_assignments', 'index'))->toContain('user_id,scope_type,scope_id');
});

it('names the tables from guardian.tables, and the models follow', function (): void {
    migrateRoleTablesAs('acl');

    foreach (['acl_permissions', 'acl_roles', 'acl_permission_role', 'acl_role_assignments'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue($table);
    }

    expect((new RoleModel)->getTable())->toBe('acl_roles')
        ->and((new PermissionModel)->getTable())->toBe('acl_permissions')
        ->and((new RoleAssignmentModel)->getTable())->toBe('acl_role_assignments')
        ->and(RoleModel::query()->pluck('slug')->sort()->values()->all())->toBe(['admin', 'member', 'super-admin']);
});

it('types user_id by guardian.users.key_type', function (string $keyType, array $types): void {
    migrateRoleTablesAs('keyed_'.$keyType, $keyType);

    expect(Schema::getColumnType('keyed_'.$keyType.'_role_assignments', 'user_id'))->toBeIn($types);
})->with([
    'int' => ['int', ['integer', 'bigint']],
    'ulid' => ['ulid', ['varchar', 'char', 'string', 'bpchar']],
    'uuid' => ['uuid', ['varchar', 'char', 'string', 'uuid', 'bpchar']],
]);

it('leaves a table that already exists alone', function (): void {
    $before = RoleModel::query()->count();

    (require dirname(__DIR__, 3).'/database/migrations/2026_11_24_000001_create_guardian_role_tables.php')->up();

    expect(RoleModel::query()->count())->toBe($before);
});

it('seeds the built-in roles as system roles', function (): void {
    $roles = RoleModel::query()->whereNull('owner_type')->orderBy('slug')->get()
        ->mapWithKeys(fn (RoleModel $role): array => [$role->slug => [$role->scope, $role->super, $role->system]])
        ->all();

    expect($roles)->toBe([
        'admin' => ['any', false, true],
        'member' => ['any', false, true],
        'super-admin' => ['global', true, true],
    ]);
});

it('relates roles, permissions and assignments', function (): void {
    $admin = RoleModel::query()->where('slug', 'admin')->sole();
    $user = user();
    $organization = organization();

    $assignment = RoleAssignmentModel::query()->create([
        'role_id' => $admin->id,
        'user_id' => $user->getKey(),
        'scope_type' => $organization->getMorphClass(),
        'scope_id' => (string) $organization->getKey(),
    ]);

    expect($admin->permissions()->pluck('name')->all())->toContain('guardian.roles.view')
        ->and($assignment->role?->is($admin))->toBeTrue()
        ->and($assignment->user?->is($user))->toBeTrue()
        ->and($assignment->scope?->is($organization))->toBeTrue()
        ->and($admin->assignments()->count())->toBe(1)
        ->and(PermissionModel::query()->where('name', 'guardian.roles.view')->sole()->roles()->pluck('slug')->all())->toContain('admin');
});

it('gives a custom role an owner scope', function (): void {
    $organization = organization();

    $role = RoleModel::query()->create([
        'name' => 'Auditor',
        'slug' => 'auditor',
        'scope' => $organization->getMorphClass(),
        'owner_type' => $organization->getMorphClass(),
        'owner_id' => (string) $organization->getKey(),
    ]);

    expect($role->owner?->is($organization))->toBeTrue()
        ->and($role->super)->toBeFalse()
        ->and($role->system)->toBeFalse();
});

describe('Users', function (): void {
    it('resolves the user model from guardian.users.model', function (): void {
        config(['guardian.users.model' => User::class]);

        expect(app(Users::class)->model())->toBe(User::class);
    });

    it('falls back to the auth provider\'s model', function (): void {
        config(['guardian.users.model' => null, 'auth.providers.users.model' => User::class]);

        expect(app(Users::class)->model())->toBe(User::class);
    });

    it('refuses a class that is not an Eloquent model', function (): void {
        config(['guardian.users.model' => stdClass::class]);

        app(Users::class)->model();
    })->throws(InvalidArgumentException::class, 'guardian.users.model');

    it('finds users by key and by email, case-insensitively', function (): void {
        $ada = user(['email' => 'Ada@Example.com']);

        expect(app(Users::class)->find($ada->getKey())?->is($ada))->toBeTrue()
            ->and(app(Users::class)->find(999999))->toBeNull()
            ->and(app(Users::class)->findByEmail('ada@example.com')?->is($ada))->toBeTrue()
            ->and(app(Users::class)->findByEmail('nobody@example.com'))->toBeNull()
            ->and(app(Users::class)->findByEmail(''))->toBeNull();
    });

    it('says whether a user\'s email is verified', function (): void {
        expect(app(Users::class)->emailVerified(user(['email_verified_at' => now()])))->toBeTrue()
            ->and(app(Users::class)->emailVerified(user(['email_verified_at' => null])))->toBeFalse();
    });
});
