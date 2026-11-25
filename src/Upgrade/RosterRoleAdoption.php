<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Upgrade;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Guardian\Domains\Permission\Services\GuardianPermissions;
use RefactorCircus\Guardian\Domains\Role\Models\RoleModel;
use RefactorCircus\Guardian\Domains\Role\Support\BuiltInRoles;
use RefactorCircus\Guardian\Support\Tables;
use stdClass;

/**
 * Copies Roster v0.1's role tables into Guardian's (criteria 20 and 21,
 * D-007).
 *
 * Runs from the `2026_11_24_000003` migration and from
 * `php artisan guardian:adopt-roster-roles`. It plans first, then copies in
 * one transaction, then syncs the built-ins:
 *
 * - nothing to adopt while any of the four source tables
 *   (`guardian.adopt.roster_tables`) is missing;
 * - already adopted when a Guardian role keeps a Roster role's ULID;
 * - nothing changes when Guardian holds an assignment or a role other than
 *   the seeded built-ins;
 * - otherwise the seeded built-ins make way for copies of Roster's rows,
 *   ULIDs kept: `organization` and `team` scopes become the morph types in
 *   `guardian.adopt.scope_types`, a custom role's organization its owner, an
 *   assignment's team (or else its organization) its scope, and Roster's
 *   role, SSO and SCIM permissions their `guardian.*` names.
 *
 * It never alters or drops a source table. Roster's table, permission and
 * scope names appear here as strings: the adoption is about Roster by
 * definition (architecture/dependencies departure,
 * docs/agent/standards-feedback.md).
 */
final class RosterRoleAdoption
{
    /**
     * Roster's permission names => Guardian's.
     *
     * @var array<string, string>
     */
    public const array PERMISSIONS = [
        'roster.roles.view' => 'guardian.roles.view',
        'roster.roles.manage' => 'guardian.roles.manage',
        'roster.roles.assign' => 'guardian.roles.assign',
        'roster.sso.view' => 'guardian.sso.view',
        'roster.sso.manage' => 'guardian.sso.manage',
        'roster.scim.manage' => 'guardian.scim.manage',
    ];

    /**
     * The source tables by key, as Roster v0.1 named them.
     *
     * @var array<string, string>
     */
    public const array TABLES = [
        'permissions' => 'roster_permissions',
        'roles' => 'roster_roles',
        'permission_role' => 'roster_permission_role',
        'role_assignments' => 'roster_role_assignments',
    ];

    /**
     * Roster's scopes => the model class (or morph alias) they become.
     *
     * @var array<string, string>
     */
    public const array SCOPE_TYPES = [
        'organization' => 'RefactorCircus\\Roster\\Domains\\Organization\\Models\\OrganizationModel',
        'team' => 'RefactorCircus\\Roster\\Domains\\Team\\Models\\TeamModel',
    ];

    public function __construct(
        private readonly Repository $config,
        private readonly DatabaseManager $db,
    ) {}

    public function run(): AdoptionReport
    {
        $report = new AdoptionReport;
        $tables = $this->sourceTables();
        $schema = $this->connection()->getSchemaBuilder();
        $missing = array_values(array_filter($tables, fn (string $table): bool => ! $schema->hasTable($table)));

        if ($missing !== []) {
            return $report->nothing($missing);
        }

        $sourceRoles = $this->connection()->table($tables['roles'])->pluck('id')->map(strval(...))->all();

        if ($sourceRoles !== [] && RoleModel::query()->whereIn('id', $sourceRoles)->exists()) {
            return $report->already();
        }

        if (! $this->onlySeeded()) {
            return $report->guardianHoldsRoles();
        }

        $scopeTypes = $this->scopeTypes();
        [$copied, $renamed] = $this->copy($tables, $scopeTypes);

        BuiltInRoles::sync();

        // Written with the query builder, so no model event forgot anything.
        app(GuardianPermissions::class)->forgetEveryone();

        return $report->adopted($copied, $renamed, $scopeTypes);
    }

    /**
     * Whether Guardian holds nothing but the seeded built-in roles: no
     * assignment, and no role that is not a shared system built-in.
     */
    private function onlySeeded(): bool
    {
        if ($this->connection()->table(Tables::name('role_assignments'))->exists()) {
            return false;
        }

        return ! RoleModel::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('system', false)
                ->orWhereNotNull('owner_type')
                ->orWhereNotIn('slug', array_keys(BuiltInRoles::roles())))
            ->exists();
    }

    /**
     * Copy every row in one transaction.
     *
     * @param  array<string, string>  $tables
     * @param  array<string, string>  $scopeTypes
     * @return array{0: array<string, int>, 1: array<string, string>}
     */
    private function copy(array $tables, array $scopeTypes): array
    {
        $connection = $this->connection();

        return $connection->transaction(function () use ($connection, $tables, $scopeTypes): array {
            // The seeded built-ins make way; the sync recreates any Roster lacks.
            $connection->table(Tables::name('permission_role'))->delete();
            $connection->table(Tables::name('roles'))->delete();

            [$permissionIds, $renamed, $permissions] = $this->copyPermissions($connection, $tables['permissions']);

            $roles = 0;

            foreach ($connection->table($tables['roles'])->orderBy('id')->get() as $role) {
                $connection->table(Tables::name('roles'))->insert($this->role($role, $scopeTypes));
                $roles++;
            }

            $grants = [];

            foreach ($connection->table($tables['permission_role'])->get() as $grant) {
                $permission = $permissionIds[$this->string($grant->permission_id ?? null)] ?? null;

                if ($permission !== null) {
                    $grants[$this->string($grant->role_id ?? null).'|'.$permission] = ['role_id' => $this->string($grant->role_id ?? null), 'permission_id' => $permission];
                }
            }

            foreach (array_chunk(array_values($grants), 200) as $chunk) {
                $connection->table(Tables::name('permission_role'))->insert($chunk);
            }

            $assignments = 0;

            foreach ($connection->table($tables['role_assignments'])->orderBy('id')->get() as $assignment) {
                $connection->table(Tables::name('role_assignments'))->insert($this->assignment($assignment, $scopeTypes));
                $assignments++;
            }

            return [[
                'permissions' => $permissions,
                'roles' => $roles,
                'grants' => count($grants),
                'assignments' => $assignments,
            ], $renamed];
        });
    }

    /**
     * Each Roster permission under its Guardian name: an existing Guardian
     * row keeps its id, a new one keeps Roster's.
     *
     * @return array{0: array<string, string>, 1: array<string, string>, 2: int}
     */
    private function copyPermissions(Connection $connection, string $table): array
    {
        $target = Tables::name('permissions');
        $ids = [];
        $renamed = [];
        $copied = 0;

        foreach ($connection->table($table)->orderBy('name')->get() as $permission) {
            $from = $this->string($permission->name ?? null);
            $name = self::PERMISSIONS[$from] ?? $from;

            if ($name !== $from) {
                $renamed[$from] = $name;
            }

            $existing = $connection->table($target)->where('name', $name)->value('id');

            if ($existing === null) {
                $existing = $this->string($permission->id ?? null);

                $connection->table($target)->insert([
                    'id' => $existing,
                    'name' => $name,
                    'description' => $permission->description ?? null,
                    'system' => (bool) ($permission->system ?? false),
                    'created_at' => $permission->created_at ?? null,
                    'updated_at' => $permission->updated_at ?? null,
                ]);
                $copied++;
            }

            $ids[$this->string($permission->id ?? null)] = $this->string($existing);
        }

        ksort($renamed);

        return [$ids, $renamed, $copied];
    }

    /**
     * @param  array<string, string>  $scopeTypes
     * @return array<string, mixed>
     */
    private function role(stdClass $role, array $scopeTypes): array
    {
        $scope = $this->string($role->scope ?? null);
        $organization = $role->organization_id ?? null;

        return [
            'id' => $this->string($role->id ?? null),
            'name' => $this->string($role->name ?? null),
            'slug' => $this->string($role->slug ?? null),
            'scope' => $scope === RoleModel::GLOBAL ? RoleModel::GLOBAL : ($scopeTypes[$scope] ?? $scope),
            'owner_type' => $organization === null ? null : $scopeTypes['organization'],
            'owner_id' => $organization === null ? null : $this->string($organization),
            'description' => $role->description ?? null,
            'super' => (bool) ($role->super ?? false),
            'system' => (bool) ($role->system ?? false),
            'created_at' => $role->created_at ?? null,
            'updated_at' => $role->updated_at ?? null,
        ];
    }

    /**
     * @param  array<string, string>  $scopeTypes
     * @return array<string, mixed>
     */
    private function assignment(stdClass $assignment, array $scopeTypes): array
    {
        $team = $assignment->team_id ?? null;
        $organization = $assignment->organization_id ?? null;

        [$type, $id] = match (true) {
            $team !== null => [$scopeTypes['team'], $this->string($team)],
            $organization !== null => [$scopeTypes['organization'], $this->string($organization)],
            default => [null, null],
        };

        return [
            'id' => $this->string($assignment->id ?? null),
            'role_id' => $this->string($assignment->role_id ?? null),
            'user_id' => $assignment->user_id ?? null,
            'scope_type' => $type,
            'scope_id' => $id,
            'created_at' => $assignment->created_at ?? null,
            'updated_at' => $assignment->updated_at ?? null,
        ];
    }

    /**
     * The four source tables by key, from `guardian.adopt.roster_tables`.
     *
     * @return array<string, string>
     */
    private function sourceTables(): array
    {
        $tables = [];

        foreach (self::TABLES as $key => $default) {
            $table = $this->config->get("guardian.adopt.roster_tables.{$key}");
            $tables[$key] = is_string($table) && $table !== '' ? $table : $default;
        }

        return $tables;
    }

    /**
     * Roster's scopes as the morph types Guardian stores: a configured model
     * class becomes its morph class, anything else is kept as an alias.
     *
     * @return array<string, string>
     */
    private function scopeTypes(): array
    {
        $types = [];

        foreach (self::SCOPE_TYPES as $scope => $default) {
            $type = $this->config->get("guardian.adopt.scope_types.{$scope}");
            $type = is_string($type) && $type !== '' ? $type : $default;

            $types[$scope] = class_exists($type) && is_subclass_of($type, Model::class) ? (new $type)->getMorphClass() : $type;
        }

        return $types;
    }

    private function string(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function connection(): Connection
    {
        return $this->db->connection();
    }
}
