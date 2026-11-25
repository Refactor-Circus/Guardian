<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Domains\Role\Support\BuiltInRoles;
use RefactorCircus\Guardian\Exceptions\RollbackRefusedException;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Guardian\Support\UserKey;

/**
 * Guardian's roles and permissions (criterion 6): permissions, roles (with
 * where they may be assigned and an optional owner scope), the grants
 * between them, and who holds which role where. Then the permissions and
 * built-in roles are synced (criterion 7).
 *
 * Needs nothing from Roster. A table already in place is left alone. down()
 * drops nothing while anyone holds a role or an admin's own role or
 * permission exists, unless `guardian.migrations.force_rollback` is on: the
 * seeded system rows alone do not hold it back (D-031).
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissions = Tables::name('permissions');
        $roles = Tables::name('roles');
        $assignments = Tables::name('role_assignments');

        if (! Schema::hasTable($permissions)) {
            Schema::create($permissions, function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->string('name')->unique();
                $table->string('description', 1000)->nullable();
                $table->boolean('system')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable($roles)) {
            Schema::create($roles, function (Blueprint $table) use ($roles): void {
                $table->ulid('id')->primary();
                $table->string('name');
                $table->string('slug');
                // `global`, `any`, or a scope model's morph type.
                $table->string('scope');
                // A custom role's own scope; null for a shared role.
                $table->string('owner_type')->nullable();
                $table->string('owner_id')->nullable();
                $table->string('description', 1000)->nullable();
                $table->boolean('super')->default(false);
                $table->boolean('system')->default(false);
                $table->timestamps();

                $table->index(['scope', 'owner_type', 'owner_id', 'slug'], $roles.'_lookup_index');
            });
        }

        if (! Schema::hasTable(Tables::name('permission_role'))) {
            Schema::create(Tables::name('permission_role'), function (Blueprint $table) use ($roles, $permissions): void {
                $table->foreignUlid('role_id')->constrained($roles)->cascadeOnDelete();
                $table->foreignUlid('permission_id')->constrained($permissions)->cascadeOnDelete();

                $table->primary(['role_id', 'permission_id']);
            });
        }

        if (! Schema::hasTable($assignments)) {
            Schema::create($assignments, function (Blueprint $table) use ($roles, $assignments): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('role_id')->constrained($roles)->cascadeOnDelete();
                UserKey::column($table, 'user_id');
                // The scope model's morph class and key; both null for global.
                $table->string('scope_type')->nullable();
                $table->string('scope_id')->nullable();
                $table->timestamps();

                $table->unique(['role_id', 'user_id', 'scope_type', 'scope_id'], $assignments.'_unique');
                // A user's grants in a scope: every permission check reads this.
                $table->index(['user_id', 'scope_type', 'scope_id'], $assignments.'_user_index');
            });
        }

        BuiltInRoles::sync();
    }

    public function down(): void
    {
        $this->refuseOverData();

        Schema::dropIfExists(Tables::name('role_assignments'));
        Schema::dropIfExists(Tables::name('permission_role'));
        Schema::dropIfExists(Tables::name('roles'));
        Schema::dropIfExists(Tables::name('permissions'));
    }

    /**
     * Throw, dropping nothing, while anyone holds a role or a role or
     * permission that is not a seeded system one exists. Only a literal true
     * forces it.
     */
    private function refuseOverData(): void
    {
        if (Config::get('guardian.migrations.force_rollback') === true) {
            return;
        }

        $checks = [
            Tables::name('role_assignments') => false,
            Tables::name('roles') => true,
            Tables::name('permissions') => true,
        ];

        $withData = [];

        foreach ($checks as $table => $onlyCustom) {
            $query = Schema::hasTable($table) ? DB::table($table) : null;

            if ($query !== null && ($onlyCustom ? $query->where('system', false) : $query)->exists()) {
                $withData[] = $table;
            }
        }

        if ($withData !== []) {
            throw RollbackRefusedException::forTables($withData);
        }
    }
};
