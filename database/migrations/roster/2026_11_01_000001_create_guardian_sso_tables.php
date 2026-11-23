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
 * SSO connections and the identities linked through them, with Roster's
 * columns, keys and indexes (from Roster's create, lookup-index and
 * performance-index migrations).
 *
 * A table already in place is left alone: the upgrade from Roster renames
 * Roster's tables into place, rows and all, before this runs. For the same
 * reason down() drops nothing while any of the tables it drops holds rows,
 * unless `guardian.migrations.force_rollback` is on (D-031).
 */
return new class extends Migration
{
    public function up(): void
    {
        $connections = Tables::name('sso_connections');

        if (! Schema::hasTable($connections)) {
            Schema::create($connections, function (Blueprint $table) use ($connections): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('organization_id')->constrained(RosterTables::name('organizations'))->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('protocol', 8);
                // Encrypted JSON: issuer, client id and secret, or SAML IdP details.
                $table->text('config');
                $table->boolean('jit')->default(true);
                $table->boolean('enforced')->default(false);
                $table->boolean('enabled')->default(true);
                $table->timestamps();

                $table->index('organization_id', $connections.'_organization_id_lookup_index');
            });
        }

        if (! Schema::hasTable(Tables::name('sso_identities'))) {
            Schema::create(Tables::name('sso_identities'), function (Blueprint $table) use ($connections): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('connection_id')->constrained($connections)->cascadeOnDelete();
                UserKey::column($table, 'user_id')->index();
                $table->string('subject');
                $table->string('email')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();

                $table->unique(['connection_id', 'subject']);
            });
        }
    }

    public function down(): void
    {
        $this->refuseOverRows();

        Schema::dropIfExists(Tables::name('sso_identities'));
        Schema::dropIfExists(Tables::name('sso_connections'));
    }

    /**
     * Throw, dropping nothing, while any of the tables this migration drops
     * holds rows (D-040): another migration's rows never hold it back. Only
     * a literal true forces it: anything else keeps the rows.
     */
    private function refuseOverRows(): void
    {
        if (Config::get('guardian.migrations.force_rollback') === true) {
            return;
        }

        $withRows = array_values(array_filter(
            array_map(Tables::name(...), ['sso_connections', 'sso_identities']),
            fn (string $table): bool => Schema::hasTable($table) && DB::table($table)->exists(),
        ));

        if ($withRows !== []) {
            throw RollbackRefusedException::forTables($withRows);
        }
    }
};
