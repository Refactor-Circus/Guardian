<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Guardian\Exceptions\RollbackRefusedException;
use RefactorCircus\Guardian\Support\Tables;
use RefactorCircus\Roster\Support\Tables as RosterTables;
use RefactorCircus\Roster\Support\UserKey;

/**
 * SCIM tokens and the users and groups provisioned through them, with
 * Roster's columns, keys and indexes (from Roster's create, lookup-index and
 * performance-index migrations).
 *
 * A table already in place is left alone: the upgrade from Roster renames
 * Roster's tables into place, rows and all, before this runs. For the same
 * reason down() drops nothing while any of Guardian's tables holds rows,
 * unless `guardian.migrations.force_rollback` is on (D-031).
 */
return new class extends Migration
{
    /**
     * The columns an organization-scoped list filters and sorts by.
     *
     * @var array<int, string>
     */
    private const array LISTED = ['organization_id', 'created_at', 'id'];

    public function up(): void
    {
        $organizations = RosterTables::name('organizations');

        $tokens = Tables::name('scim_tokens');

        if (! Schema::hasTable($tokens)) {
            Schema::create($tokens, function (Blueprint $table) use ($tokens, $organizations): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('organization_id')->constrained($organizations)->cascadeOnDelete();
                $table->string('name');
                $table->string('token_hash', 64)->unique();
                $table->foreignUlid('sso_connection_id')->nullable()->constrained(Tables::name('sso_connections'))->nullOnDelete();
                UserKey::column($table, 'created_by')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index('organization_id', $tokens.'_organization_id_lookup_index');
                $table->index('sso_connection_id', $tokens.'_sso_connection_id_lookup_index');
            });
        }

        $users = Tables::name('scim_users');

        if (! Schema::hasTable($users)) {
            Schema::create($users, function (Blueprint $table) use ($users, $organizations): void {
                // Its id is the SCIM resource id the identity provider sees.
                $table->ulid('id')->primary();
                $table->foreignUlid('organization_id')->constrained($organizations)->cascadeOnDelete();
                UserKey::column($table, 'user_id');
                $table->string('external_id')->nullable();
                $table->boolean('created_by_scim')->default(false);
                $table->boolean('deactivated_by_scim')->default(false);
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->unique(['organization_id', 'user_id']);
                $table->index(['organization_id', 'external_id']);
                $table->index(self::LISTED, $users.'_organization_created_index');
            });
        }

        $groups = Tables::name('scim_groups');

        if (! Schema::hasTable($groups)) {
            Schema::create($groups, function (Blueprint $table) use ($groups, $organizations): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('organization_id')->constrained($organizations)->cascadeOnDelete();
                $table->foreignUlid('team_id')->unique()->constrained(RosterTables::name('teams'))->cascadeOnDelete();
                $table->string('external_id')->nullable();
                $table->timestamps();

                $table->index('organization_id', $groups.'_organization_id_lookup_index');
                $table->index(self::LISTED, $groups.'_organization_created_index');
            });
        }
    }

    public function down(): void
    {
        $this->refuseOverRows();

        Schema::dropIfExists(Tables::name('scim_groups'));
        Schema::dropIfExists(Tables::name('scim_users'));
        Schema::dropIfExists(Tables::name('scim_tokens'));
    }

    /**
     * Throw, dropping nothing, while any of Guardian's tables holds rows.
     * Every table counts, not only this migration's: this migration rolls
     * back first, and must not drop its tables when the sso migration after
     * it is going to refuse. Only a literal true forces it: anything else
     * keeps the rows.
     */
    private function refuseOverRows(): void
    {
        if (Config::get('guardian.migrations.force_rollback') === true) {
            return;
        }

        $withRows = array_values(array_filter(
            array_map(Tables::name(...), array_keys(Tables::DEFAULTS)),
            fn (string $table): bool => Schema::hasTable($table) && DB::table($table)->exists(),
        ));

        if ($withRows !== []) {
            throw RollbackRefusedException::forTables($withRows);
        }
    }
};
