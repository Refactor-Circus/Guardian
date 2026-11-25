<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Roster's role tables as Roster v0.1 created them (its
 * `2026_01_01_000003_create_roster_role_tables` migration at the pinned SHA),
 * with its built-in roles, for the states an install upgrading from v0.1
 * reaches: Roster v2 no longer creates them, but leaves an existing
 * install's tables for the host to drop. Table names follow `roster.tables.*`
 * as Roster's did. Run by the tests that need them, never by the base suite.
 */
return new class extends Migration
{
    public function up(): void
    {
        $name = fn (string $key): string => (string) (config("roster.tables.{$key}") ?? 'roster_'.$key);

        Schema::create($name('permissions'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('description', 1000)->nullable();
            $table->boolean('system')->default(false);
            $table->timestamps();
        });

        Schema::create($name('roles'), function (Blueprint $table) use ($name): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug');
            $table->string('scope', 32);
            $table->foreignUlid('organization_id')->nullable()->constrained($name('organizations'))->cascadeOnDelete();
            $table->string('description', 1000)->nullable();
            $table->boolean('super')->default(false);
            $table->boolean('system')->default(false);
            $table->timestamps();

            $table->index(['scope', 'organization_id', 'slug']);
        });

        Schema::create($name('permission_role'), function (Blueprint $table) use ($name): void {
            $table->foreignUlid('role_id')->constrained($name('roles'))->cascadeOnDelete();
            $table->foreignUlid('permission_id')->constrained($name('permissions'))->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create($name('role_assignments'), function (Blueprint $table) use ($name): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('role_id')->constrained($name('roles'))->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->foreignUlid('organization_id')->nullable()->index()->constrained($name('organizations'))->cascadeOnDelete();
            $table->foreignUlid('team_id')->nullable()->index()->constrained($name('teams'))->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id', 'organization_id', 'team_id'], 'legacy_roster_role_assignments_unique');
            $table->index(['user_id', 'organization_id', 'team_id'], 'legacy_roster_role_assignments_lookup');
        });

        $now = now();

        foreach ([
            'super-admin' => ['Super admin', 'global', true],
            'admin' => ['Admin', 'organization', false],
            'member' => ['Member', 'organization', false],
            'lead' => ['Team lead', 'team', false],
        ] as $slug => [$label, $scope, $super]) {
            DB::table($name('roles'))->insert([
                'id' => (string) Str::ulid(),
                'name' => $label,
                'slug' => $slug,
                'scope' => $scope,
                'super' => $super,
                'system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $name = fn (string $key): string => (string) (config("roster.tables.{$key}") ?? 'roster_'.$key);

        foreach (['role_assignments', 'permission_role', 'roles', 'permissions'] as $key) {
            Schema::dropIfExists($name($key));
        }
    }
};
